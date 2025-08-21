<?php

namespace Database\Seeders;

use App\Models\FoodType;
use Illuminate\Database\Seeder;

class FoodTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $foodTypes = [
            [
                'name' => 'Chicken Breast',
                'description' => 'Lean protein source, skinless',
                'calories_per_100g' => 165,
                'protein_per_100g' => 31,
                'carbs_per_100g' => 0,
                'fat_per_100g' => 3.6,
                'category' => 'Protein',
            ],
            [
                'name' => 'Brown Rice',
                'description' => 'Whole grain carbohydrate',
                'calories_per_100g' => 123,
                'protein_per_100g' => 2.6,
                'carbs_per_100g' => 23,
                'fat_per_100g' => 0.9,
                'category' => 'Carbohydrates',
            ],
            [
                'name' => 'Broccoli',
                'description' => 'Nutrient-dense green vegetable',
                'calories_per_100g' => 34,
                'protein_per_100g' => 2.8,
                'carbs_per_100g' => 7,
                'fat_per_100g' => 0.4,
                'category' => 'Vegetables',
            ],
            [
                'name' => 'Salmon',
                'description' => 'Fatty fish rich in omega-3',
                'calories_per_100g' => 208,
                'protein_per_100g' => 25,
                'carbs_per_100g' => 0,
                'fat_per_100g' => 12,
                'category' => 'Protein',
            ],
            [
                'name' => 'Sweet Potato',
                'description' => 'Complex carbohydrate with vitamins',
                'calories_per_100g' => 86,
                'protein_per_100g' => 1.6,
                'carbs_per_100g' => 20,
                'fat_per_100g' => 0.1,
                'category' => 'Carbohydrates',
            ],
            [
                'name' => 'Eggs',
                'description' => 'Complete protein source',
                'calories_per_100g' => 155,
                'protein_per_100g' => 13,
                'carbs_per_100g' => 1.1,
                'fat_per_100g' => 11,
                'category' => 'Protein',
            ],
            [
                'name' => 'Oats',
                'description' => 'Whole grain breakfast cereal',
                'calories_per_100g' => 389,
                'protein_per_100g' => 17,
                'carbs_per_100g' => 66,
                'fat_per_100g' => 7,
                'category' => 'Carbohydrates',
            ],
            [
                'name' => 'Almonds',
                'description' => 'Nutrient-dense nuts',
                'calories_per_100g' => 579,
                'protein_per_100g' => 21,
                'carbs_per_100g' => 22,
                'fat_per_100g' => 50,
                'category' => 'Fats',
            ],
            [
                'name' => 'Greek Yogurt',
                'description' => 'High-protein dairy product',
                'calories_per_100g' => 59,
                'protein_per_100g' => 10,
                'carbs_per_100g' => 3.6,
                'fat_per_100g' => 0.4,
                'category' => 'Protein',
            ],
            [
                'name' => 'Banana',
                'description' => 'Natural fruit high in potassium',
                'calories_per_100g' => 89,
                'protein_per_100g' => 1.1,
                'carbs_per_100g' => 23,
                'fat_per_100g' => 0.3,
                'category' => 'Fruits',
            ],
        ];

        foreach ($foodTypes as $foodType) {
            FoodType::create($foodType);
        }
    }
}
