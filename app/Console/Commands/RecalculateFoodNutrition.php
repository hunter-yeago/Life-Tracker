<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Models\FoodType;
use Illuminate\Console\Command;

class RecalculateFoodNutrition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'food:recalculate-nutrition {--food-type-id= : Only recalculate for specific food type} {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate stored nutritional values for food entries based on current food type values';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $foodTypeId = $this->option('food-type-id');
        $dryRun = $this->option('dry-run');

        $query = Food::with('foodType');

        if ($foodTypeId) {
            $query->where('food_type_id', $foodTypeId);
        }

        $foods = $query->get();

        if ($foods->isEmpty()) {
            $this->info('No food entries found to recalculate.');
            return 0;
        }

        $this->info("Found {$foods->count()} food entries to process.");

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made');
        }

        $bar = $this->output->createProgressBar($foods->count());
        $updatedCount = 0;

        foreach ($foods as $food) {
            $foodType = $food->foodType;
            
            if (!$foodType) {
                $this->warn("Food entry {$food->id} has no associated food type. Skipping.");
                $bar->advance();
                continue;
            }

            // Calculate expected values based on servings
            $servings = $food->servings ?? 1;
            $expectedCalories = $foodType->calories_per_serving * $servings;
            $expectedProtein = $foodType->protein_per_serving * $servings;
            $expectedCarbs = $foodType->carbs_per_serving * $servings;
            $expectedFat = $foodType->fat_per_serving * $servings;

            // Check if values need updating (with small tolerance for floating point)
            $needsUpdate = abs($food->total_calories - $expectedCalories) > 0.01 ||
                          abs($food->total_protein - $expectedProtein) > 0.01 ||
                          abs($food->total_carbs - $expectedCarbs) > 0.01 ||
                          abs($food->total_fat - $expectedFat) > 0.01;

            if ($needsUpdate) {
                if ($dryRun) {
                    $this->line("Would update {$foodType->name} (ID: {$food->id}, Date: {$food->consumed_at}):");
                    $this->line("  Calories: {$food->total_calories} → {$expectedCalories}");
                    $this->line("  Protein: {$food->total_protein} → {$expectedProtein}");
                    $this->line("  Carbs: {$food->total_carbs} → {$expectedCarbs}");
                    $this->line("  Fat: {$food->total_fat} → {$expectedFat}");
                } else {
                    $food->update([
                        'total_calories' => $expectedCalories,
                        'total_protein' => $expectedProtein,
                        'total_carbs' => $expectedCarbs,
                        'total_fat' => $expectedFat,
                    ]);
                }
                $updatedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("Dry run complete. {$updatedCount} entries would be updated.");
        } else {
            $this->info("Successfully updated {$updatedCount} food entries.");
        }

        return 0;
    }
}
