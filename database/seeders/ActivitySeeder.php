<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Activity::query()->forceDelete();

        $trips = Trip::all();

        foreach ($trips as $trip) {
            $startDate = Carbon::parse($trip->start_date);

            Activity::create([
                'trip_id' => $trip->id,
                'title' => 'Visita al centro histórico',
                'description' => 'Explorar los puntos principales de la ciudad.',
                'date' => $startDate->toDateString(),
                'time' => '10:00',
                'location' => 'Centro de '.$trip->destination,
                'cost' => 50.00,
            ]);

            Activity::create([
                'trip_id' => $trip->id,
                'title' => 'Cena típica',
                'description' => 'Probar gastronomía local en un restaurante recomendado.',
                'date' => $startDate->copy()->addDay()->toDateString(),
                'time' => '20:00',
                'location' => 'Restaurante local',
                'cost' => 30.00,
            ]);
        }
    }
}
