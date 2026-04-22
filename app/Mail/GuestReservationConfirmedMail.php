<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestReservationConfirmedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Reservation $reservation,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reserva confirmada {$this->reservation->reservation_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guest-reservation-confirmed',
        );
    }
}
