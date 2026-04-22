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
        try {
            $event = $this->stripeCheckoutService->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException) {
            return response()->json([
                'message' => 'Invalid Stripe webhook signature.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $session = $event->data->object;

        if (!isset($session->id)) {
            return response()->json(['received' => true]);
        }

        $reservation = Reservation::query()
            ->where('stripe_checkout_session_id', $session->id)
            ->orWhere('reservation_code', data_get($session, 'metadata.reservation_code'))
            ->first();

        if (! $reservation) {
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
        }

        return response()->json(['received' => true]);
    }
}
