<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationEmailService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeCheckoutService $stripeCheckoutService,
        protected ReservationEmailService $reservationEmailService,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        Log::info('Stripe webhook received.', [
            'signature_present' => $request->hasHeader('Stripe-Signature'),
        ]);

        try {
            $event = $this->stripeCheckoutService->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException) {
            Log::warning('Stripe webhook signature verification failed.');

            return response()->json([
                'message' => 'Invalid Stripe webhook signature.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $session = $event->data->object;

        Log::info('Stripe webhook verified.', [
            'event_type' => $event->type,
            'session_id' => $session->id ?? null,
            'reservation_code' => data_get($session, 'metadata.reservation_code'),
        ]);

        if (!isset($session->id)) {
            Log::warning('Stripe webhook event did not contain a session id.', [
                'event_type' => $event->type,
            ]);

            return response()->json(['received' => true]);
        }

        $reservation = Reservation::query()
            ->where('stripe_checkout_session_id', $session->id)
            ->orWhere('reservation_code', data_get($session, 'metadata.reservation_code'))
            ->first();

        if (! $reservation) {
            Log::warning('Stripe webhook did not match any reservation.', [
                'event_type' => $event->type,
                'session_id' => $session->id,
                'reservation_code' => data_get($session, 'metadata.reservation_code'),
            ]);

            return response()->json(['received' => true]);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $wasAlreadyPaid = $reservation->payment_status === PaymentStatus::Paid;

            $reservation->forceFill([
                'status' => ReservationStatus::Confirmed->value,
                'payment_status' => PaymentStatus::Paid->value,
                'stripe_payment_intent_id' => is_string($session->payment_intent ?? null) ? $session->payment_intent : null,
                'paid_at' => now(),
                'cancelled_at' => null,
            ])->save();

            Log::info('Reservation marked as paid from Stripe webhook.', [
                'reservation_id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'session_id' => $session->id,
                'event_type' => $event->type,
                'already_paid' => $wasAlreadyPaid,
            ]);

            if (! $wasAlreadyPaid) {
                $this->reservationEmailService->sendPaymentConfirmedEmails($reservation->fresh(['room']));
            }
        }

        if (in_array($event->type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $reservation->forceFill([
                'status' => ReservationStatus::Cancelled->value,
                'payment_status' => PaymentStatus::Failed->value,
                'cancelled_at' => now(),
            ])->save();

            Log::info('Reservation marked as failed from Stripe webhook.', [
                'reservation_id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'session_id' => $session->id,
                'event_type' => $event->type,
            ]);
        }

        return response()->json(['received' => true]);
    }
}
