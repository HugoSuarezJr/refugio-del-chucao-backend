<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Services\ReservationService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Throwable;

class StripeCheckoutController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected StripeCheckoutService $stripeCheckoutService,
    ) {
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = null;

        try {
            $reservation = $this->reservationService->create([
                ...$request->validated(),
                'payment_status' => PaymentStatus::Pending->value,
                'source' => 'stripe_checkout',
            ]);

            $session = $this->stripeCheckoutService->createCheckoutSession($reservation);

            $reservation->forceFill([
                'stripe_checkout_session_id' => $session->id,
            ])->save();
        } catch (Throwable $exception) {
            if ($reservation?->exists) {
                $reservation->delete();
            }

            report($exception);

            return response()->json([
                'message' => 'No se pudo iniciar el pago con Stripe.',
            ], 500);
        }

        return response()->json([
            'reservation' => new ReservationResource($reservation->fresh(['room'])),
            'checkout_url' => $session->url,
            'checkout_session_id' => $session->id,
        ], 201);
    }
}
