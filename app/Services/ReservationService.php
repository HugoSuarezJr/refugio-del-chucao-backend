<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected PricingService $pricingService,
    ) {
    }

    public function create(array $payload): Reservation
    {
        $room = Room::query()->where('slug', $payload['room_id'])->firstOrFail();
        return $this->persistReservation($room, $payload);
    }

    public function createAdmin(Room $room, array $payload): Reservation
    {
        return $this->persistReservation($room, $payload);
    }

    public function updateAdmin(Reservation $reservation, array $payload): Reservation
    {
        return $this->persistReservation($reservation->room()->firstOrFail(), $payload, $reservation);
    }

    protected function generateReservationCode(): string
    {
        return 'RDC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    protected function persistReservation(Room $room, array $payload, ?Reservation $reservation = null): Reservation
    {
        $checkIn = Carbon::parse($payload['check_in'])->startOfDay();
        $checkOut = Carbon::parse($payload['check_out'])->startOfDay();
        $guests = (int) $payload['number_of_guests'];

        $this->assertRoomIsAvailable($room, $checkIn, $checkOut, $reservation);

        $pricing = $this->pricingService->calculate($room, $checkIn, $checkOut, $guests);

        $reservation ??= new Reservation([
            'reservation_code' => $this->generateReservationCode(),
        ]);

        $reservation->fill([
            'room_id' => $room->id,
            'guest_name' => $payload['guest_name'],
            'guest_email' => $payload['guest_email'],
            'guest_phone' => $payload['guest_phone'] ?? null,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'number_of_guests' => $guests,
            'status' => $payload['status'] ?? ReservationStatus::Pending->value,
            'payment_status' => $payload['payment_status'] ?? PaymentStatus::Unpaid->value,
            'currency' => $pricing['currency'],
            'subtotal' => $pricing['subtotal'],
            'fees_total' => $pricing['fee_total'],
            'total' => $pricing['total'],
            'pricing_breakdown' => $pricing,
            'notes' => $payload['notes'] ?? null,
            'source' => $payload['source'] ?? config('booking.default_reservation_source'),
        ]);

        $reservation->save();

        return $reservation->fresh(['room']);
    }

    protected function assertRoomIsAvailable(Room $room, Carbon $checkIn, Carbon $checkOut, ?Reservation $ignoreReservation = null): void
    {
        $hasReservationConflict = Reservation::query()
            ->where('room_id', $room->id)
            ->blocking()
            ->when($ignoreReservation, fn ($query) => $query->whereKeyNot($ignoreReservation->getKey()))
            ->whereDate('check_in', '<', $checkOut->toDateString())
            ->whereDate('check_out', '>', $checkIn->toDateString())
            ->exists();

        $hasBlockConflict = RoomBlock::query()
            ->where('room_id', $room->id)
            ->whereDate('start_date', '<', $checkOut->toDateString())
            ->whereDate('end_date', '>', $checkIn->toDateString())
            ->exists();

        if ($hasReservationConflict || $hasBlockConflict) {
            throw ValidationException::withMessages([
                'check_in' => 'La habitación no está disponible para esas fechas.',
            ]);
        }
    }
}
