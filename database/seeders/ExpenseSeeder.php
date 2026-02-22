<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Expense::query()->forceDelete();

        $trips = Trip::all();

        foreach ($trips as $trip) {
            $startDate = Carbon::parse($trip->start_date);

            Expense::create([
                'trip_id' => $trip->id,
                'amount' => 150.00,
                'description' => 'Transporte aeropuerto',
                'date' => $startDate->toDateString(),
                'category' => 'Transporte',
            ]);

            Expense::create([
                'trip_id' => $trip->id,
                'amount' => 75.50,
                'description' => 'Cena en restaurante',
                'date' => $startDate->copy()->addDay()->toDateString(),
                'category' => 'Comida',
            ]);

            Expense::create([
                'trip_id' => $trip->id,
                'amount' => 200.00,
                'description' => 'Entrada a museo/tour',
                'date' => $startDate->copy()->addDays(2)->toDateString(),
                'category' => 'Entretenimiento',
            ]);
        }
    }
}
