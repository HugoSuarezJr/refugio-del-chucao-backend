<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminSeasonalRateRequest;
use App\Http\Resources\SeasonalRateResource;
use App\Models\Room;
use App\Models\SeasonalRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSeasonalRateController extends Controller
{
    public function index(Room $room): AnonymousResourceCollection
    {
        $rates = SeasonalRate::query()
            ->where('room_id', $room->id)
            ->orderBy('start_date')
            ->orderByDesc('priority')
            ->get();

        return SeasonalRateResource::collection($rates);
    }

    public function store(AdminSeasonalRateRequest $request, Room $room): JsonResponse
    {
        $validated = $request->validated();

        $rate = SeasonalRate::query()->create([
            'room_id' => $room->id,
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'nightly_rate' => $validated['nightly_rate'],
            'currency' => strtoupper($validated['currency'] ?? $room->currency ?? config('booking.currency')),
            'priority' => $validated['priority'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        return (new SeasonalRateResource($rate->load('room')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(AdminSeasonalRateRequest $request, SeasonalRate $seasonalRate): SeasonalRateResource
    {
        $validated = $request->validated();

        $seasonalRate->fill([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'nightly_rate' => $validated['nightly_rate'],
            'currency' => strtoupper($validated['currency'] ?? $seasonalRate->currency ?? config('booking.currency')),
            'priority' => $validated['priority'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        $seasonalRate->save();

        return new SeasonalRateResource($seasonalRate->fresh('room'));
    }

    public function destroy(SeasonalRate $seasonalRate): JsonResponse
    {
        $seasonalRate->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }
}
