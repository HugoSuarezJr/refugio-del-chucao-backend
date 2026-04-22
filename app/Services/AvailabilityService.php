<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomBlock;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AvailabilityService
{
    public function __construct(
        protected PricingService $pricingService,
    ) {
    }

    public function forRoom(Room $room, CarbonInterface|string $checkIn, CarbonInterface|string $checkOut, int $guests = 1): array
    {
        $checkIn = Carbon::parse($checkIn)->startOfDay();
        $checkOut = Carbon::parse($checkOut)->startOfDay();

        $conflicts = [
            ...$this->reservationConflicts($room, $checkIn, $checkOut),
            ...$this->blockConflicts($room, $checkIn, $checkOut),
        ];

        return [
            'available' => count($conflicts) === 0,
            'conflicts' => $conflicts,
            'pricing' => $this->pricingService->calculate($room, $checkIn, $checkOut, $guests),
        ];
    }

    public function calendarConflicts(Room $room, CarbonInterface|string $startsAt, CarbonInterface|string $endsAt): array
    {
        $startsAt = Carbon::parse($startsAt)->startOfDay();
        $endsAt = Carbon::parse($endsAt)->startOfDay();

        return [
            ...$this->reservationConflicts($room, $startsAt, $endsAt),
            ...$this->blockConflicts($room, $startsAt, $endsAt),
        ];
    }

    protected function reservationConflicts(Room $room, CarbonInterface $checkIn, CarbonInterface $checkOut): array
    {
        return Reservation::query()
            ->where('room_id', $room->id)
            ->blocking()
            ->whereDate('check_in', '<', $checkOut->toDateString())
            ->whereDate('check_out', '>', $checkIn->toDateString())
            ->orderBy('check_in')
            ->get()
            ->map(fn (Reservation $reservation) => [
                'type' => 'reservation',
                'starts_at' => $reservation->check_in->toDateString(),
                'ends_at' => $reservation->check_out->toDateString(),
                'status' => $reservation->status->value,
                'reference' => $reservation->reservation_code,
            ])
            ->all();
    }

    protected function blockConflicts(Room $room, CarbonInterface $checkIn, CarbonInterface $checkOut): array
    {
        return RoomBlock::query()
            ->where('room_id', $room->id)
            ->whereDate('start_date', '<', $checkOut->toDateString())
            ->whereDate('end_date', '>', $checkIn->toDateString())
            ->orderBy('start_date')
            ->get()
            ->map(fn (RoomBlock $block) => [
                'type' => 'block',
                'starts_at' => $block->start_date->toDateString(),
                'ends_at' => $block->end_date->toDateString(),
                'status' => $block->source->value,
                'reason' => $block->reason,
            ])
            ->all();
    }
}
