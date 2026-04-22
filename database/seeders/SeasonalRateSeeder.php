<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\SeasonalRate;
use Illuminate\Database\Seeder;

class SeasonalRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['slug' => 'martin-pescador', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2025-12-01', 'end_date' => '2026-03-31', 'nightly_rate' => 120000, 'priority' => 10],
            ['slug' => 'siete-colores', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2025-12-01', 'end_date' => '2026-03-31', 'nightly_rate' => 120000, 'priority' => 10],
            ['slug' => 'la-bandurria', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2025-12-01', 'end_date' => '2026-03-31', 'nightly_rate' => 120000, 'priority' => 10],
            ['slug' => 'las-taguas', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2025-12-01', 'end_date' => '2026-03-31', 'nightly_rate' => 120000, 'priority' => 10],
            ['slug' => 'martin-pescador', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2026-12-01', 'end_date' => '2027-03-31', 'nightly_rate' => 120000, 'priority' => 10],
            ['slug' => 'siete-colores', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2026-12-01', 'end_date' => '2027-03-31', 'nightly_rate' => 120000, 'priority' => 10],
            ['slug' => 'la-bandurria', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2026-12-01', 'end_date' => '2027-03-31', 'nightly_rate' => 120000, 'priority' => 10],
            ['slug' => 'las-taguas', 'name' => 'Temporada alta diciembre-marzo', 'start_date' => '2026-12-01', 'end_date' => '2027-03-31', 'nightly_rate' => 120000, 'priority' => 10],
        ];

        foreach ($rates as $rate) {
            $room = Room::query()->where('slug', $rate['slug'])->first();

            if (! $room) {
                continue;
            }

            SeasonalRate::query()->updateOrCreate(
                [
                    'room_id' => $room->id,
                    'name' => $rate['name'],
                    'start_date' => $rate['start_date'],
                    'end_date' => $rate['end_date'],
                ],
                [
                    'nightly_rate' => $rate['nightly_rate'],
                    'currency' => config('booking.currency'),
                    'priority' => $rate['priority'],
                    'is_active' => true,
                ],
            );
        }
    }
}
