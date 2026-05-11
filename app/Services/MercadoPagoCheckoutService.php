<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoCheckoutService
{
    public function createCheckoutPreference(Reservation $reservation): array
    {
        $frontendUrl = rtrim((string) config('services.mercado_pago.frontend_url'), '/');
        $backendUrl = rtrim((string) config('app.url'), '/');
        $roomSlug = $reservation->room?->slug ?? $reservation->room()->value('slug');
        $roomName = $reservation->room?->name ?? $roomSlug;
        $holdMinutes = (int) config('booking.pending_payment_hold_minutes', 30);
        $payload = [
            'items' => [[
                'id' => (string) $reservation->id,
                'title' => "Reserva {$roomName}",
                'description' => sprintf(
                    '%s al %s · %d noche(s)',
                    $reservation->check_in?->toDateString(),
                    $reservation->check_out?->toDateString(),
                    (int) data_get($reservation->pricing_breakdown, 'nights', 0),
                ),
                'quantity' => 1,
                'currency_id' => strtoupper($reservation->currency),
                'unit_price' => (float) $reservation->total,
            ]],
            'payer' => array_filter([
                'name' => $reservation->guest_name,
                'email' => $reservation->guest_email,
            ]),
            'external_reference' => $reservation->reservation_code,
            'notification_url' => "{$backendUrl}/api/mercado-pago/webhook",
            'back_urls' => [
                'success' => "{$frontendUrl}/habitacion/{$roomSlug}?payment=success&reservation={$reservation->reservation_code}",
                'pending' => "{$frontendUrl}/habitacion/{$roomSlug}?payment=pending&reservation={$reservation->reservation_code}",
                'failure' => "{$frontendUrl}/habitacion/{$roomSlug}?payment=cancelled&reservation={$reservation->reservation_code}",
            ],
            'expires' => true,
            'expiration_date_from' => now()->toIso8601String(),
            'expiration_date_to' => now()->addMinutes($holdMinutes)->toIso8601String(),
            'metadata' => [
                'reservation_id' => (string) $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'room_slug' => (string) $roomSlug,
            ],
        ];

        if (! $this->isLocalUrl($frontendUrl)) {
            $payload['auto_return'] = 'approved';
        }

        return $this->client()->post('/checkout/preferences', $payload)->throw()->json();
    }

    public function hasValidWebhookSignature(Request $request): bool
    {
        $secret = (string) config('services.mercado_pago.webhook_secret');

        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->header('x-signature', '');
        $requestId = (string) $request->header('x-request-id', '');
        $dataId = $this->extractWebhookResourceId($request);

        if ($signature === '' || $requestId === '' || $dataId === null) {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signature) as $chunk) {
            [$key, $value] = array_pad(explode('=', trim($chunk), 2), 2, null);

            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['ts'] ?? null;
        $hash = $parts['v1'] ?? null;

        if ($timestamp === null || $hash === null) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hash);
    }

    public function extractWebhookPaymentId(Request $request): ?string
    {
        $type = strtolower((string) (
            $request->query('type')
            ?? $request->input('type')
            ?? $request->query('topic')
            ?? $request->input('topic')
            ?? ''
        ));

        $action = strtolower((string) $request->input('action', ''));

        if ($type !== '' && ! in_array($type, ['payment', 'payments'], true) && ! str_starts_with($type, 'payment.')) {
            return null;
        }

        if ($type === '' && $action !== '' && ! str_starts_with($action, 'payment.')) {
            return null;
        }

        return $this->extractWebhookResourceId($request);
    }

    public function getPayment(string $paymentId): array
    {
        return $this->client()->get("/v1/payments/{$paymentId}")
            ->throw()
            ->json();
    }

    protected function extractWebhookResourceId(Request $request): ?string
    {
        $resourceId = $request->query('data_id')
            ?? $request->query('id')
            ?? data_get($request->input(), 'data.id')
            ?? null;

        if (is_scalar($resourceId) && (string) $resourceId !== '') {
            return (string) $resourceId;
        }

        $resource = $request->query('resource')
            ?? $request->input('resource')
            ?? null;

        if (is_string($resource) && $resource !== '') {
            $segments = explode('/', trim($resource, '/'));
            $lastSegment = end($segments);

            if (is_string($lastSegment) && $lastSegment !== '') {
                return $lastSegment;
            }
        }

        return null;
    }

    protected function client(): PendingRequest
    {
        $accessToken = (string) config('services.mercado_pago.access_token');

        if ($accessToken === '') {
            throw new RuntimeException('Mercado Pago access token is not configured.');
        }

        return Http::baseUrl((string) config('services.mercado_pago.base_url'))
            ->acceptJson()
            ->asJson()
            ->withToken($accessToken);
    }

    protected function isLocalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return true;
        }

        return in_array($host, ['localhost', '127.0.0.1'], true);
    }
}
