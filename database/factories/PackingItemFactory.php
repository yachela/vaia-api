<?php

namespace Database\Factories;

use App\Models\PackingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PackingItem>
 */
class PackingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Documentación', 'Higiene', 'Ropa', 'Tecnología'];
        
        return [
            'packing_list_id' => PackingList::factory(),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement($categories),
            'is_packed' => fake()->boolean(30), // 30% chance of being packed
            'is_suggested' => fake()->boolean(20), // 20% chance of being suggested
            'suggestion_reason' => fake()->boolean(20) ? fake()->sentence() : null,
        ];
    }
}

