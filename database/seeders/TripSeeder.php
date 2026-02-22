<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereIn('email', ['test@example.com', 'admin@example.com'])->get();

        if ($users->isEmpty()) {
            return;
        }

        // Keep seeding idempotent for local testing runs.
        Trip::query()->forceDelete();

        $tripsByUser = [
            'test@example.com' => [
                [
                    'title' => 'Vacaciones en Bariloche',
                    'destination' => 'Bariloche, Argentina',
                    'start_date' => '2026-03-15',
                    'end_date' => '2026-03-22',
                    'budget' => 50000.00,
                ],
                [
                    'title' => 'Viaje a Europa',
                    'destination' => 'París, Francia',
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-06-15',
                    'budget' => 150000.00,
                ],
                [
                    'title' => 'Aventura en Perú',
                    'destination' => 'Cusco, Perú',
                    'start_date' => '2026-10-05',
                    'end_date' => '2026-10-12',
                    'budget' => 25000.00,
                ],
            ],
            'admin@example.com' => [
                [
                    'title' => 'Escapada a Mendoza',
                    'destination' => 'Mendoza, Argentina',
                    'start_date' => '2026-04-08',
                    'end_date' => '2026-04-12',
                    'budget' => 32000.00,
                ],
                [
                    'title' => 'Semana en Río',
                    'destination' => 'Río de Janeiro, Brasil',
                    'start_date' => '2026-12-20',
                    'end_date' => '2026-12-30',
                    'budget' => 40000.00,
                ],
            ],
        ];

        foreach ($users as $user) {
            foreach ($tripsByUser[$user->email] ?? [] as $tripData) {
                Trip::create([
                    'user_id' => $user->id,
                    ...$tripData,
                ]);
            }
        }
    }
}
