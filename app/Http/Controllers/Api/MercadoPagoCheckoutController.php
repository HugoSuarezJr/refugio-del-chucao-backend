<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Services\MercadoPagoCheckoutService;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Throwable;

class MercadoPagoCheckoutController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected MercadoPagoCheckoutService $mercadoPagoCheckoutService,
    ) {
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = null;

        try {
            $reservation = $this->reservationService->create([
                ...$request->validated(),
                'payment_status' => PaymentStatus::Pending->value,
                'source' => 'mercado_pago_checkout',
            ]);

            $preference = $this->mercadoPagoCheckoutService->createCheckoutPreference($reservation);

            $reservation->forceFill([
                'mercado_pago_preference_id' => data_get($preference, 'id'),
            ])->save();
        } catch (Throwable $exception) {
            if ($reservation?->exists) {
                $reservation->delete();
            }

            report($exception);

            return response()->json([
                'message' => 'No se pudo iniciar el pago con Mercado Pago.',
            ], 500);
        }

        $checkoutUrl = data_get($preference, 'init_point') ?? data_get($preference, 'sandbox_init_point');

        return response()->json([
            'reservation' => new ReservationResource($reservation->fresh(['room'])),
            'checkout_url' => $checkoutUrl,
            'checkout_preference_id' => data_get($preference, 'id'),
            'checkout_session_id' => data_get($preference, 'id'),
        ], 201);
    }
}
