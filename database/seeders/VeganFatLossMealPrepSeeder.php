<?php

namespace Database\Seeders;

use App\Models\FoodType;
use Illuminate\Database\Seeder;

class VeganFatLossMealPrepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recipes = [
            [
                'name' => 'Berry Overnight Oats',
                'description' => 'Peanut butter overnight oats topped with raspberries, blueberries, and chia seeds',
                'calories_per_serving' => 447,
                'protein_per_serving' => 22,
                'carbs_per_serving' => 60,
                'fat_per_serving' => 15,
            ],
            [
                'name' => 'Tofu Veggie Power Bowl',
                'description' => 'Quinoa, air-fried tofu, and edamame with cucumber, carrots, radishes, spinach, kimchi, and orange miso dressing',
                'calories_per_serving' => 607,
                'protein_per_serving' => 39,
                'carbs_per_serving' => 75,
                'fat_per_serving' => 17,
            ],
            [
                'name' => 'Romesco Seitan Polenta Bowl',
                'description' => 'Kale, polenta, and homemade seitan with roasted chickpeas and smoked romesco sauce',
                'calories_per_serving' => 468,
                'protein_per_serving' => 49,
                'carbs_per_serving' => 46,
                'fat_per_serving' => 11,
            ],
        ];

        foreach ($recipes as $recipe) {
            FoodType::firstOrCreate(
                ['name' => $recipe['name']],
                array_merge($recipe, [
                    'serving_size' => '1 serving',
                    'category' => 'Food',
                    'is_one_time_item' => false,
                ])
            );
        }
    }
}
