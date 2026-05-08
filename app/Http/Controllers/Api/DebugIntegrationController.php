<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugIntegrationController extends Controller
{
    public function __construct(
        protected ReservationEmailService $reservationEmailService,
    ) {
    }

    public function status(Request $request): JsonResponse
    {
        if (! $this->hasValidToken($request)) {
            return response()->json(['message' => 'Not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'app_env' => app()->environment(),
            'app_url' => config('app.url'),
            'log_channel' => config('logging.default'),
            'log_stack' => config('logging.channels.stack.channels'),
            'mail_mailer' => config('mail.default'),
            'mail_from_address' => config('mail.from.address'),
            'booking_admin_notification_email' => config('booking.admin_notification_email'),
            'stripe_secret_key_configured' => filled(config('services.stripe.secret_key')),
            'stripe_publishable_key_configured' => filled(config('services.stripe.publishable_key')),
            'stripe_webhook_secret_configured' => filled(config('services.stripe.webhook_secret')),
        ]);
    }

    public function markReservationPaid(Request $request, string $reservationCode): JsonResponse
    {
        if (! $this->hasValidToken($request)) {
            return response()->json(['message' => 'Not found.'], Response::HTTP_NOT_FOUND);
        }

        $reservation = Reservation::query()
            ->where('reservation_code', $reservationCode)
            ->first();

        if (! $reservation) {
            return response()->json([
                'message' => 'Reservation not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $wasAlreadyPaid = $reservation->payment_status === PaymentStatus::Paid;

        $reservation->forceFill([
            'status' => ReservationStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
            'cancelled_at' => null,
        ])->save();

        Log::info('Debug reservation payment confirmation invoked.', [
            'reservation_id' => $reservation->id,
            'reservation_code' => $reservation->reservation_code,
            'already_paid' => $wasAlreadyPaid,
        ]);

        if (! $wasAlreadyPaid) {
            $this->reservationEmailService->sendPaymentConfirmedEmails($reservation->fresh(['room']));
        }

        return response()->json([
            'reservation_code' => $reservation->reservation_code,
            'status' => $reservation->status->value,
            'payment_status' => $reservation->payment_status->value,
            'emails_attempted' => ! $wasAlreadyPaid,
        ]);
    }

    protected function hasValidToken(Request $request): bool
    {
        $configuredToken = (string) env('DEBUG_INTEGRATION_TOKEN', '');
        $providedToken = (string) ($request->header('X-Debug-Token') ?? $request->query('token', ''));

        return $configuredToken !== '' && hash_equals($configuredToken, $providedToken);
    }
}
