<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MinimalDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Expense::query()->forceDelete();
        Activity::query()->forceDelete();
        Trip::query()->forceDelete();

        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        $trip = Trip::create([
            'user_id' => $user->id,
            'title' => 'Demo Trip',
            'destination' => 'Buenos Aires, Argentina',
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-23',
            'budget' => 20000.00,
        ]);

        Activity::create([
            'trip_id' => $trip->id,
            'title' => 'City walk',
            'description' => 'Recorrido corto por el centro.',
            'date' => '2026-03-20',
            'time' => '10:00',
            'location' => 'Microcentro',
            'cost' => 0.00,
        ]);

        Expense::create([
            'trip_id' => $trip->id,
            'amount' => 120.00,
            'description' => 'Taxi aeropuerto',
            'date' => '2026-03-20',
            'category' => 'Transporte',
        ]);
    }
}
