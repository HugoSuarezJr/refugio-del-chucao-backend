<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class AdminReservationController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
    ) {
    }

    public function store(AdminReservationRequest $request, Room $room): JsonResponse
    {
        $reservation = $this->reservationService->createAdmin($room, $request->validated());

        return (new ReservationResource($reservation->load('room')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(AdminReservationRequest $request, Reservation $reservation): ReservationResource
    {
        $updatedReservation = $this->reservationService->updateAdmin($reservation, $request->validated());

        return new ReservationResource($updatedReservation->load('room'));
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }
}
