<?php

namespace App\Services;

use App\Models\Reservation;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutService
{
    public function createCheckoutSession(Reservation $reservation): Session
    {
        $frontendUrl = rtrim((string) config('services.stripe.frontend_url'), '/');
        $roomSlug = $reservation->room?->slug ?? $reservation->room()->value('slug');
        $roomName = $reservation->room?->name ?? $roomSlug;

        return $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'expires_at' => now()->addMinutes((int) config('booking.pending_payment_hold_minutes', 30))->timestamp,
            'success_url' => "{$frontendUrl}/habitacion/{$roomSlug}?payment=success&reservation={$reservation->reservation_code}",
            'cancel_url' => "{$frontendUrl}/habitacion/{$roomSlug}?payment=cancelled&reservation={$reservation->reservation_code}",
            'customer_email' => $reservation->guest_email,
            'locale' => 'es-419',
            'metadata' => [
                'reservation_id' => (string) $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'room_slug' => (string) $roomSlug,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($reservation->currency),
                    'unit_amount' => $reservation->total,
                    'product_data' => [
                        'name' => "Reserva {$roomName}",
                        'description' => sprintf(
                            '%s al %s · %d noche(s)',
                            $reservation->check_in?->toDateString(),
                            $reservation->check_out?->toDateString(),
                            (int) data_get($reservation->pricing_breakdown, 'nights', 0),
                        ),
                    ],
                ],
            ]],
        ]);
    }

    public function constructWebhookEvent(string $payload, ?string $signature): Event
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            throw new SignatureVerificationException('Missing Stripe webhook secret.', null);
        }

        return Webhook::constructEvent($payload, $signature ?? '', $secret);
    }

    protected function client(): StripeClient
    {
        $secretKey = (string) config('services.stripe.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return new StripeClient($secretKey);
    }
}
