@php
    $roomName = $reservation->room?->name ?? 'N/A';
@endphp

@include('emails.layout', [
    'title' => 'Nueva reserva pagada',
    'eyebrow' => 'Nueva reserva pagada',
    'headline' => 'Entró una nueva reserva confirmada',
    'subheadline' => "Pago recibido para {$roomName}. Revisa los datos del huésped y prepara la llegada.",
    'slot' => view('emails.partials.admin-reservation-paid-content', [
        'reservation' => $reservation,
        'roomName' => $roomName,
    ])->render(),
])
