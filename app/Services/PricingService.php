<?php

namespace App\Services;

use App\Models\Room;
use App\Models\SeasonalRate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PricingService
{
    public function calculate(Room $room, CarbonInterface|string $checkIn, CarbonInterface|string $checkOut, int $guests = 1): array
    {
        $checkIn = Carbon::parse($checkIn)->startOfDay();
        $checkOut = Carbon::parse($checkOut)->startOfDay();
        $nights = $checkIn->diffInDays($checkOut);

        $seasonalRates = SeasonalRate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($room) {
                $query->whereNull('room_id')
                    ->orWhere('room_id', $room->id);
            })
            ->whereDate('start_date', '<=', $checkOut->copy()->subDay()->toDateString())
            ->whereDate('end_date', '>=', $checkIn->toDateString())
            ->orderByDesc('room_id')
            ->orderByDesc('priority')
            ->get();

        $nightlyRates = [];
        $subtotal = 0;

        for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
            $rate = $this->rateForDate($room, $date, $seasonalRates);
            $nightlyRates[] = $rate;
            $subtotal += $rate['amount'];
        }

        $fees = [];
        $feeTotal = 0;

        return [
            'currency' => $room->currency,
            'guests' => $guests,
            'nights' => $nights,
            'nightly_rates' => $nightlyRates,
            'subtotal' => $subtotal,
            'fees' => $fees,
            'fee_total' => $feeTotal,
            'total' => $subtotal + $feeTotal,
        ];
    }

    protected function rateForDate(Room $room, CarbonInterface $date, Collection $seasonalRates): array
    {
        $seasonalRate = $seasonalRates->first(function (SeasonalRate $rate) use ($date) {
            return $date->betweenIncluded(
                Carbon::parse($rate->start_date)->startOfDay(),
                Carbon::parse($rate->end_date)->endOfDay(),
            );
        });

        return [
            'date' => $date->toDateString(),
            'amount' => $seasonalRate?->nightly_rate ?? $room->base_nightly_rate,
            'source' => $seasonalRate ? 'seasonal_rate' : 'base_rate',
            'season_name' => $seasonalRate?->name,
        ];
    }
}
