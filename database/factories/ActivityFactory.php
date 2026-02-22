<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => \App\Models\Trip::factory(),
            'title' => $this->faker->sentence(2),
            'description' => $this->faker->paragraph,
            'date' => $this->faker->date(),
            'time' => $this->faker->time('H:i'),
            'location' => $this->faker->address,
            'cost' => $this->faker->randomFloat(2, 0, 500),
        ];
    }
}
