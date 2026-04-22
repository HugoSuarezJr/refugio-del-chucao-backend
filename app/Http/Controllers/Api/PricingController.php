<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PricingCalculateRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
    ) {
    }

    public function calculate(PricingCalculateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $room = Room::query()->where('slug', $validated['room_id'])->firstOrFail();

        return response()->json([
            'room' => new RoomResource($room),
            'pricing' => $this->pricingService->calculate(
                $room,
                $validated['check_in'],
                $validated['check_out'],
                (int) ($validated['number_of_guests'] ?? 1),
            ),
        ]);
    }
}
