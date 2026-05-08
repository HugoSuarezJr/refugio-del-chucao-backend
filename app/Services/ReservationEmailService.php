<?php

namespace App\Services;

use App\Mail\AdminReservationPaidMail;
use App\Mail\GuestReservationConfirmedMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReservationEmailService
{
    public function sendPaymentConfirmedEmails(Reservation $reservation): void
    {
        $reservation->loadMissing('room');
        $adminEmail = config('booking.admin_notification_email');

        Log::info('Sending reservation payment confirmation emails.', [
            'reservation_id' => $reservation->id,
            'reservation_code' => $reservation->reservation_code,
            'guest_email' => $reservation->guest_email,
            'admin_email' => is_string($adminEmail) && $adminEmail !== '' ? $adminEmail : null,
        ]);

        try {
            Mail::to($reservation->guest_email)->send(
                new GuestReservationConfirmedMail($reservation)
            );

            if (is_string($adminEmail) && $adminEmail !== '') {
                Mail::to($adminEmail)->send(
                    new AdminReservationPaidMail($reservation)
                );
            }

            Log::info('Reservation payment confirmation emails sent.', [
                'reservation_id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
            ]);
        } catch (Throwable $exception) {
            Log::error('Reservation payment confirmation emails failed.', [
                'reservation_id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'message' => $exception->getMessage(),
            ]);

            report($exception);
        }
    }
}
