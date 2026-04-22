@php
    $roomName = $reservation->room?->name ?? 'Tu habitación';
    $contactEmail = config('mail.from.address');
@endphp

@include('emails.layout', [
    'title' => 'Reserva confirmada',
    'eyebrow' => 'Reserva confirmada',
    'headline' => 'Tu estadía ya está asegurada',
    'subheadline' => "Recibimos tu pago para {$roomName}. Te esperamos en la Patagonia con todo listo para tu llegada.",
    'slot' => view('emails.partials.guest-reservation-confirmed-content', [
        'reservation' => $reservation,
        'roomName' => $roomName,
        'contactEmail' => $contactEmail,
    ])->render(),
])
