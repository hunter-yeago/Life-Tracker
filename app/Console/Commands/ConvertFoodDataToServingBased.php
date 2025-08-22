<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConvertFoodDataToServingBased extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:food-data-to-serving-based {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert existing food data from per-100g to per-serving calculations and set default serving sizes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE: No changes will be made to the database.');
        }

        // Get all food types that don't have serving size set
        $foodTypes = \App\Models\FoodType::whereNull('serving_size')->get();
        
        $this->info("Found {$foodTypes->count()} food types to update...");
        
        foreach ($foodTypes as $foodType) {
            $this->line("Processing: {$foodType->name}");
            
            // Set default serving size and weight for different categories
            $servingSize = '1 serving';
            $servingWeightGrams = 100; // Default to 100g serving
            
            // Adjust serving size based on category or name patterns
            $name = strtolower($foodType->name);
            $category = strtolower($foodType->category ?? '');
            
            if (str_contains($name, 'cup') || str_contains($category, 'beverage')) {
                $servingSize = '1 cup';
                $servingWeightGrams = 240; // 1 cup ≈ 240ml/g for liquids
            } elseif (str_contains($name, 'slice') || str_contains($category, 'bread')) {
                $servingSize = '1 slice';
                $servingWeightGrams = 30;
            } elseif (str_contains($name, 'can') || str_contains($category, 'canned')) {
                $servingSize = '1 can';
                $servingWeightGrams = 400;
            } elseif (str_contains($category, 'fruit')) {
                $servingSize = '1 medium';
                $servingWeightGrams = 150;
            } elseif (str_contains($category, 'vegetable')) {
                $servingSize = '1 cup';
                $servingWeightGrams = 100;
            }
            
            if (!$isDryRun) {
                $foodType->update([
                    'serving_size' => $servingSize,
                    'serving_weight_grams' => $servingWeightGrams,
                ]);
                
                $this->line("  → Updated with serving size: {$servingSize} ({$servingWeightGrams}g)");
            } else {
                $this->line("  → Would update with serving size: {$servingSize} ({$servingWeightGrams}g)");
            }
        }
        
        // Now update existing food entries to calculate servings
        $foodEntries = \App\Models\Food::whereNull('servings')->get();
        
        $this->info("Found {$foodEntries->count()} food entries to update with serving calculations...");
        
        foreach ($foodEntries as $food) {
            $foodType = $food->foodType;
            
            if ($foodType && $foodType->serving_weight_grams && $food->quantity_grams) {
                $servings = $food->quantity_grams / $foodType->serving_weight_grams;
                
                if (!$isDryRun) {
                    $food->update(['servings' => round($servings, 2)]);
                    $this->line("  → Updated food entry #{$food->id} with {$servings} servings");
                } else {
                    $this->line("  → Would update food entry #{$food->id} with {$servings} servings");
                }
            }
        }
        
        $this->info('Data conversion completed!');
    }
}
