<?php

namespace Database\Factories;

use App\Models\FoodType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodType>
 */
class FoodTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'serving_size' => '1 serving',
            'calories_per_serving' => fake()->numberBetween(100, 800),
            'protein_per_serving' => fake()->numberBetween(0, 50),
            'carbs_per_serving' => fake()->numberBetween(0, 100),
            'fat_per_serving' => fake()->numberBetween(0, 50),
            'category' => 'Food',
            'is_one_time_item' => false,
        ];
    }
}
