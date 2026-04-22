<?php

namespace App\Services;

use App\Mail\AdminReservationPaidMail;
use App\Mail\GuestReservationConfirmedMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReservationEmailService
{
    public function sendPaymentConfirmedEmails(Reservation $reservation): void
    {
        $reservation->loadMissing('room');

        try {
            Mail::to($reservation->guest_email)->send(
                new GuestReservationConfirmedMail($reservation)
            );

            $adminEmail = config('booking.admin_notification_email');

            if (is_string($adminEmail) && $adminEmail !== '') {
                Mail::to($adminEmail)->send(
                    new AdminReservationPaidMail($reservation)
                );
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
