<?php

namespace App\Console\Commands;

use App\Models\FoodType;
use Illuminate\Console\Command;

class NormalizeFoodData extends Command
{
    protected $signature = 'normalize:food-data';
    protected $description = 'Normalize food data and import unique food items into database';

    public function handle(): int
    {
        $rawData = [
            [
                "brand" => "La Tourangelle",
                "product" => "California Extra Virgin Olive Oil",
                "serving_size" => "1 tbsp",
                "calories" => 130,
                "carbs" => 0,
                "protein" => 0,
                "fats" => 14
            ],
            [
                "brand" => "Signature Select",
                "product" => "Ripe Pitted Olives",
                "serving_size" => "5 olives",
                "calories" => 20,
                "carbs" => 1,
                "protein" => 0,
                "fats" => 1.5
            ],
            [
                "brand" => "Generic",
                "product" => "Almond Milk",
                "serving_size" => "1 serving",
                "calories" => 30,
                "carbs" => 1,
                "protein" => 1,
                "fats" => 2.5
            ],
            [
                "brand" => "Generic",
                "product" => "Firm Tofu",
                "serving_size" => "1/2 block",
                "calories" => 210,
                "carbs" => 4,
                "protein" => 22,
                "fats" => 13
            ],
            // Use the 1 block version for the base serving
            [
                "brand" => "Generic",
                "product" => "Firm Tofu",
                "serving_size" => "1 block",
                "calories" => 420,
                "carbs" => 8,
                "protein" => 44,
                "fats" => 26
            ],
            [
                "brand" => "Mission",
                "product" => "Whole Wheat Tortilla",
                "serving_size" => "1 tortilla",
                "calories" => 60,
                "carbs" => 20,
                "protein" => 5,
                "fats" => 4
            ],
            // Large tortillas (6 pack version)
            [
                "brand" => "Mission",
                "product" => "Large Whole Wheat Tortilla",
                "serving_size" => "1 tortilla", // Normalize from 6 tortillas
                "calories" => 60, // 360/6
                "carbs" => 20, // 120/6  
                "protein" => 7.5, // 45/6
                "fats" => 5 // 30/6
            ],
            [
                "brand" => "Preferida",
                "product" => "Black Beans",
                "serving_size" => "1 serving",
                "calories" => 110,
                "carbs" => 15,
                "protein" => 10,
                "fats" => 0
            ],
            [
                "brand" => "La Preferida",
                "product" => "Refried Beans",
                "serving_size" => "1/2 can",
                "calories" => 210,
                "carbs" => 30,
                "protein" => 12,
                "fats" => 3
            ],
            // Use full can version for base serving
            [
                "brand" => "La Preferida", 
                "product" => "Refried Beans (Full Can)",
                "serving_size" => "1 can",
                "calories" => 420,
                "carbs" => 60,
                "protein" => 25,
                "fats" => 5
            ],
            [
                "brand" => "Generic",
                "product" => "Red Enchilada Sauce",
                "serving_size" => "1 serving",
                "calories" => 20,
                "carbs" => 3,
                "protein" => 1,
                "fats" => 0
            ],
            [
                "brand" => "Generic",
                "product" => "Spring Mix",
                "serving_size" => "100 grams",
                "calories" => 24,
                "carbs" => 4,
                "protein" => 4,
                "fats" => 0
            ],
            // Large spring mix serving
            [
                "brand" => "Generic",
                "product" => "Spring Mix (Large)",
                "serving_size" => "250 grams",
                "calories" => 60,
                "carbs" => 10,
                "protein" => 10,
                "fats" => 0
            ],
            [
                "brand" => "Generic",
                "product" => "Simply Balsamic Vinaigrette",
                "serving_size" => "1 serving (2 tbsp)",
                "calories" => 50,
                "carbs" => 3,
                "protein" => 0,
                "fats" => 4.5
            ],
            [
                "brand" => "Generic",
                "product" => "Tomato Polenta",
                "serving_size" => "1 serving",
                "calories" => 110,
                "carbs" => 24,
                "protein" => 3,
                "fats" => 0
            ],
            [
                "brand" => "Generic",
                "product" => "Handful of Small Tomatoes",
                "serving_size" => "handful",
                "calories" => 20,
                "carbs" => 4,
                "protein" => 1,
                "fats" => 0
            ],
            [
                "brand" => "Generic",
                "product" => "Green Chilis",
                "serving_size" => "1 serving",
                "calories" => 5,
                "carbs" => 1,
                "protein" => 0,
                "fats" => 0
            ],
            [
                "brand" => "Generic",
                "product" => "Rhubarb Cake (homemade)",
                "serving_size" => "half slice",
                "calories" => 100,
                "carbs" => 18,
                "protein" => 1.5,
                "fats" => 5
            ],
            [
                "brand" => "Generic",
                "product" => "Beer",
                "serving_size" => "1 beer",
                "calories" => 120,
                "carbs" => 10,
                "protein" => 0,
                "fats" => 0
            ],
            [
                "brand" => "Generic",
                "product" => "Vodka",
                "serving_size" => "1 shot",
                "calories" => 100,
                "carbs" => 0,
                "protein" => 0,
                "fats" => 0
            ],
            [
                "brand" => "Generic",
                "product" => "Avocado Plant Butter",
                "serving_size" => "1 tbsp",
                "calories" => 100,
                "carbs" => 0,
                "protein" => 0,
                "fats" => 11
            ],
            [
                "brand" => "Generic",
                "product" => "Croissant",
                "serving_size" => "1 piece",
                "calories" => 150,
                "carbs" => 20,
                "protein" => 3,
                "fats" => 7
            ],
            [
                "brand" => "Generic",
                "product" => "Vegan Ice Cream with Chocolate Shell",
                "serving_size" => "1 bowl",
                "calories" => 400,
                "carbs" => 42,
                "protein" => 4,
                "fats" => 26
            ],
            [
                "brand" => "Generic",
                "product" => "Chips & Guac",
                "serving_size" => "1 serving",
                "calories" => 460,
                "carbs" => 46,
                "protein" => 6,
                "fats" => 30
            ],
            [
                "brand" => "Generic",
                "product" => "Spinach & Artichoke Dip",
                "serving_size" => "1 serving",
                "calories" => 600,
                "carbs" => 20,
                "protein" => 10,
                "fats" => 50
            ],
            [
                "brand" => "Generic",
                "product" => "Sushi Box",
                "serving_size" => "1 box",
                "calories" => 330,
                "carbs" => 10,
                "protein" => 5,
                "fats" => 3
            ],
            [
                "brand" => "Generic",
                "product" => "Fruit/Salad Box",
                "serving_size" => "1 box",
                "calories" => 220,
                "carbs" => 17,
                "protein" => 8,
                "fats" => 14
            ],
            [
                "brand" => "Generic",
                "product" => "Fries",
                "serving_size" => "1 serving (~150g)",
                "calories" => 450,
                "carbs" => 50,
                "protein" => 5,
                "fats" => 24
            ]
        ];

        // Clear existing normalized data first
        $this->info('Clearing existing normalized food types...');
        FoodType::whereIn('category', [
            'Fats & Oils', 'Protein', 'Grains & Starches', 'Vegetables', 
            'Beverages', 'Dairy & Alternatives', 'Desserts & Sweets', 
            'Condiments & Sauces', 'Other'
        ])->delete();

        // Define standard serving sizes in grams
        $servingSizeMap = $this->getServingSizeMap();

        $this->info('Normalizing and importing unique food items...');
        $created = 0;
        $skipped = 0;

        foreach ($rawData as $item) {
            $normalized = $this->normalizeToHundredGrams($item, $servingSizeMap);
            
            if ($normalized) {
                $name = trim($item['brand'] === 'Generic' ? $item['product'] : $item['brand'] . ' ' . $item['product']);
                
                // Check if this food type already exists to avoid duplicates
                $existing = FoodType::where('name', $name)->first();
                if ($existing) {
                    $this->line("- Skipped duplicate: {$name}");
                    $skipped++;
                    continue;
                }
                
                $foodType = FoodType::create([
                    'name' => $name,
                    'description' => "Standard serving: {$item['serving_size']}",
                    'calories_per_100g' => $normalized['calories_per_100g'],
                    'protein_per_100g' => $normalized['protein_per_100g'],
                    'carbs_per_100g' => $normalized['carbs_per_100g'],
                    'fat_per_100g' => $normalized['fat_per_100g'],
                    'category' => $this->categorizeFood($item['product']),
                ]);

                $created++;
                $this->line("✓ Created: {$name} (Category: {$foodType->category})");
            } else {
                $this->warn("× Could not normalize: {$item['product']} ({$item['serving_size']})");
            }
        }

        $this->newLine();
        $this->info("Import completed!");
        $this->info("- Created: {$created} unique food types");
        $this->info("- Skipped: {$skipped} duplicates");

        return 0;
    }

    private function getServingSizeMap(): array
    {
        return [
            // Volume measurements (in ml/grams)
            '1 tbsp' => 15,
            '2 tbsp' => 30,
            '1 cup' => 240,
            '1 beer' => 355,
            '1 shot' => 44, // Standard shot size
            
            // Weight measurements (in grams)
            '100 grams' => 100,
            '250 grams' => 250,
            '1 serving (~150g)' => 150,
            
            // Piece/unit measurements (estimated weights in grams)
            '1 tortilla' => 45,
            '1 piece' => 60, // croissant
            '5 olives' => 25,
            'handful' => 50,
            'half slice' => 40,
            '1 box' => 200, // sushi/salad box
            '1 bowl' => 150, // ice cream
            
            // Standardized serving sizes (in grams)
            '1 serving' => 100, // default serving
            '1/2 block' => 175, // tofu half block
            '1 block' => 350, // tofu full block
            '1/2 can' => 120, // refried beans half can
            '1 can' => 240, // refried beans full can
            '1 serving (2 tbsp)' => 30, // dressing
        ];
    }

    private function normalizeToHundredGrams(array $item, array $servingSizeMap): ?array
    {
        $servingSize = $item['serving_size'];
        
        if (!isset($servingSizeMap[$servingSize])) {
            return null;
        }

        $servingGrams = $servingSizeMap[$servingSize];
        $multiplier = 100 / $servingGrams;

        return [
            'calories_per_100g' => round($item['calories'] * $multiplier, 1),
            'protein_per_100g' => round($item['protein'] * $multiplier, 1),
            'carbs_per_100g' => round($item['carbs'] * $multiplier, 1),
            'fat_per_100g' => round($item['fats'] * $multiplier, 1),
        ];
    }

    private function categorizeFood(string $product): string
    {
        $product = strtolower($product);
        
        if (str_contains($product, 'oil') || str_contains($product, 'butter')) {
            return 'Fats & Oils';
        }
        
        if (str_contains($product, 'bean') || str_contains($product, 'tofu')) {
            return 'Protein';
        }
        
        if (str_contains($product, 'tortilla') || str_contains($product, 'polenta') || str_contains($product, 'fries')) {
            return 'Grains & Starches';
        }
        
        if (str_contains($product, 'salad') || str_contains($product, 'spring mix') || str_contains($product, 'tomato') || str_contains($product, 'chilis')) {
            return 'Vegetables';
        }
        
        if (str_contains($product, 'beer') || str_contains($product, 'vodka')) {
            return 'Beverages';
        }
        
        if (str_contains($product, 'milk')) {
            return 'Dairy & Alternatives';
        }
        
        if (str_contains($product, 'cake') || str_contains($product, 'ice cream') || str_contains($product, 'croissant')) {
            return 'Desserts & Sweets';
        }
        
        if (str_contains($product, 'sauce') || str_contains($product, 'dressing') || str_contains($product, 'dip')) {
            return 'Condiments & Sauces';
        }
        
        return 'Other';
    }
}