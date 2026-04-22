<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityCheckRequest;
use App\Http\Requests\RoomAvailabilityRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
    ) {
    }

    public function check(AvailabilityCheckRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $guests = (int) ($validated['number_of_guests'] ?? 1);

        if (! empty($validated['room_id'])) {
            $room = Room::query()->where('slug', $validated['room_id'])->firstOrFail();
            $availability = $this->availabilityService->forRoom($room, $validated['check_in'], $validated['check_out'], $guests);

            return response()->json([
                ...$availability,
                'room' => new RoomResource($room),
            ]);
        }

        $results = Room::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(function (Room $room) use ($validated, $guests) {
                $availability = $this->availabilityService->forRoom($room, $validated['check_in'], $validated['check_out'], $guests);

                return [
                    ...$availability,
                    'room' => new RoomResource($room),
                ];
            })
            ->values();

        return response()->json([
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'number_of_guests' => $guests,
            'rooms' => $results,
            'available_rooms_count' => $results->where('available', true)->count(),
        ]);
    }

    public function show(RoomAvailabilityRequest $request, Room $room): JsonResponse
    {
        $validated = $request->validated();
        $availability = $this->availabilityService->forRoom(
            $room,
            $validated['check_in'],
            $validated['check_out'],
            (int) ($validated['number_of_guests'] ?? 1),
        );

        return response()->json([
            ...$availability,
            'room' => new RoomResource($room),
        ]);
    }

    public function calendar(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $startsAt = isset($validated['starts_at'])
            ? Carbon::parse($validated['starts_at'])->startOfDay()
            : Carbon::today()->startOfDay();

        $endsAt = isset($validated['ends_at'])
            ? Carbon::parse($validated['ends_at'])->startOfDay()
            : $startsAt->copy()->addMonths((int) ($validated['months'] ?? 12));

        $conflicts = $this->availabilityService->calendarConflicts($room, $startsAt, $endsAt);

        return response()->json([
            'room' => new RoomResource($room),
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'conflicts' => $conflicts,
        ]);
    }
}
