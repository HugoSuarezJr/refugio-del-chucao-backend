<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\MercadoPagoCheckoutService;
use App\Services\ReservationEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        protected MercadoPagoCheckoutService $mercadoPagoCheckoutService,
        protected ReservationEmailService $reservationEmailService,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        Log::info('Mercado Pago webhook received.', [
            'type' => $request->query('type', $request->input('type')),
            'action' => $request->input('action'),
        ]);

        if (! $this->mercadoPagoCheckoutService->hasValidWebhookSignature($request)) {
            Log::warning('Mercado Pago webhook signature verification failed.');

            return response()->json([
                'message' => 'Invalid Mercado Pago webhook signature.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $paymentId = $this->mercadoPagoCheckoutService->extractWebhookPaymentId($request);

        if ($paymentId === null) {
            return response()->json(['received' => true]);
        }

        $payment = $this->mercadoPagoCheckoutService->getPayment($paymentId);
        $reservationCode = (string) (
            data_get($payment, 'external_reference')
            ?? data_get($payment, 'metadata.reservation_code')
            ?? ''
        );

        Log::info('Mercado Pago payment fetched.', [
            'payment_id' => $paymentId,
            'payment_status' => data_get($payment, 'status'),
            'reservation_code' => $reservationCode,
        ]);

        if ($reservationCode === '') {
            Log::warning('Mercado Pago payment did not include a reservation reference.', [
                'payment_id' => $paymentId,
            ]);

            return response()->json(['received' => true]);
        }

        $reservation = Reservation::query()
            ->where('reservation_code', $reservationCode)
            ->first();

        if (! $reservation) {
            Log::warning('Mercado Pago webhook did not match any reservation.', [
                'payment_id' => $paymentId,
                'reservation_code' => $reservationCode,
            ]);

            return response()->json(['received' => true]);
        }

        $status = strtolower((string) data_get($payment, 'status', ''));

        if ($status === 'approved') {
            $wasAlreadyPaid = $reservation->payment_status === PaymentStatus::Paid;

            $reservation->forceFill([
                'status' => ReservationStatus::Confirmed->value,
                'payment_status' => PaymentStatus::Paid->value,
                'mercado_pago_payment_id' => (string) data_get($payment, 'id'),
                'paid_at' => $reservation->paid_at ?? now(),
                'cancelled_at' => null,
            ])->save();

            Log::info('Reservation marked as paid from Mercado Pago webhook.', [
                'reservation_id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'payment_id' => $paymentId,
                'already_paid' => $wasAlreadyPaid,
            ]);

            if (! $wasAlreadyPaid) {
                $this->reservationEmailService->sendPaymentConfirmedEmails($reservation->fresh(['room']));
            }
        }

        if (in_array($status, ['cancelled', 'rejected'], true)) {
            $reservation->forceFill([
                'status' => ReservationStatus::Cancelled->value,
                'payment_status' => PaymentStatus::Failed->value,
                'mercado_pago_payment_id' => (string) data_get($payment, 'id'),
                'cancelled_at' => now(),
            ])->save();

            Log::info('Reservation marked as failed from Mercado Pago webhook.', [
                'reservation_id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'payment_id' => $paymentId,
                'payment_status' => $status,
            ]);
        }

        if ($status === 'refunded') {
            $reservation->forceFill([
                'payment_status' => PaymentStatus::Refunded->value,
                'mercado_pago_payment_id' => (string) data_get($payment, 'id'),
            ])->save();
        }

        return response()->json(['received' => true]);
    }
}
