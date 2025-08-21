<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Models\FoodType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportFoodData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:food-data {directory} {user_id} {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import food data from JSON files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = $this->argument('directory');
        $userId = $this->argument('user_id');
        $dryRun = $this->option('dry-run');

        if (! File::isDirectory($directory)) {
            $this->error("Directory {$directory} does not exist.");

            return 1;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->error("User with ID {$userId} does not exist.");

            return 1;
        }

        $this->info("Starting import from: {$directory}");
        $this->info("Target user: {$user->name} ({$user->email})");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $jsonFiles = File::glob($directory.'/*.json');

        if (empty($jsonFiles)) {
            $this->error('No JSON files found in the specified directory.');

            return 1;
        }

        $this->info('Found '.count($jsonFiles).' JSON files');

        $totalEntries = 0;
        $createdFoodTypes = 0;
        $errors = [];

        foreach ($jsonFiles as $file) {
            $this->info('Processing: '.basename($file));

            try {
                $result = $this->processFile($file, $user, $dryRun);
                $totalEntries += $result['entries'];
                $createdFoodTypes += $result['food_types'];

                if (! empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }
            } catch (\Exception $e) {
                $this->error("Failed to process {$file}: ".$e->getMessage());
                $errors[] = "File {$file}: ".$e->getMessage();
            }
        }

        $this->newLine();
        $this->info('Import Summary:');
        $this->info("- Total food entries: {$totalEntries}");
        $this->info("- New food types created: {$createdFoodTypes}");

        if (! empty($errors)) {
            $this->warn('- Errors encountered: '.count($errors));
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
        }

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to actually import the data.');
        } else {
            $this->info('Import completed successfully!');
        }

        return 0;
    }

    private function processFile(string $filePath, User $user, bool $dryRun): array
    {
        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: '.json_last_error_msg());
        }

        // Try to extract date from filename (assuming format like YYYY-MM-DD.json)
        $filename = basename($filePath, '.json');
        $date = $this->extractDateFromFilename($filename);

        $entries = 0;
        $foodTypes = 0;
        $errors = [];

        // Handle different possible JSON structures
        $foodItems = $this->extractFoodItems($data, $date, $errors);

        foreach ($foodItems as $item) {
            try {
                if (! $dryRun) {
                    $result = $this->createFoodEntry($item, $user);
                    if ($result['created_food_type']) {
                        $foodTypes++;
                    }
                }
                $entries++;
            } catch (\Exception $e) {
                $errors[] = "Failed to create entry for {$item['name']}: ".$e->getMessage();
            }
        }

        return [
            'entries' => $entries,
            'food_types' => $foodTypes,
            'errors' => $errors,
        ];
    }

    private function extractDateFromFilename(string $filename): ?Carbon
    {
        // Try various date formats
        $patterns = [
            '/^(\d{4}-\d{2}-\d{2})/',           // YYYY-MM-DD
            '/^(\d{2}-\d{2}-\d{4})/',           // MM-DD-YYYY
            '/^(\d{4})(\d{2})(\d{2})/',         // YYYYMMDD
            '/(\d{4}-\d{2}-\d{2})/',            // YYYY-MM-DD anywhere in filename
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $filename, $matches)) {
                try {
                    if (isset($matches[3])) {
                        // YYYYMMDD format
                        return Carbon::createFromFormat('Y-m-d', $matches[1].'-'.$matches[2].'-'.$matches[3]);
                    } else {
                        return Carbon::parse($matches[1]);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    private function extractFoodItems(array $data, ?Carbon $date, array &$errors): array
    {
        $items = [];

        // Extract date from data if not from filename
        if (! $date && isset($data['date'])) {
            try {
                $date = Carbon::parse($data['date']);
            } catch (\Exception $e) {
                $errors[] = 'Failed to parse date from data: '.$data['date'];
            }
        }

        // Handle different JSON structures
        if (isset($data['meals']) && is_array($data['meals'])) {
            // Your structure: {"date": "...", "meals": [...]}
            $foodData = $data['meals'];
        } elseif (isset($data['foods']) && is_array($data['foods'])) {
            // Structure: {"foods": [...]}
            $foodData = $data['foods'];
        } elseif (isset($data['entries']) && is_array($data['entries'])) {
            // Structure: {"entries": [...]}
            $foodData = $data['entries'];
        } elseif (is_array($data) && ! empty($data)) {
            // Direct array structure
            $foodData = $data;
        } else {
            $errors[] = 'Unknown JSON structure';

            return [];
        }

        foreach ($foodData as $item) {
            $processed = $this->processFoodItem($item, $date);
            if ($processed) {
                $items[] = $processed;
            } else {
                $errors[] = 'Failed to process item: '.json_encode($item);
            }
        }

        return $items;
    }

    private function processFoodItem(array $item, ?Carbon $date): ?array
    {
        // Extract food name from your structure
        $name = $item['food'] ?? $item['product'] ?? $item['name'] ?? $item['food_name'] ?? $item['title'] ?? null;

        // For your data, we'll use portion as notes and estimate quantity from nutritional density
        $portion = $item['portion'] ?? $item['serving_size'] ?? null;

        // Extract nutritional info
        $calories = $item['calories'] ?? $item['total_calories'] ?? 0;
        $protein = $item['protein'] ?? $item['total_protein'] ?? 0;
        $carbs = $item['carbs'] ?? $item['carbohydrates'] ?? $item['total_carbs'] ?? 0;
        $fat = $item['fat'] ?? $item['total_fat'] ?? $item['fats'] ?? 0;

        // Extract brand info if available
        $brand = $item['brand'] ?? null;
        if ($brand && $brand !== 'Generic') {
            $name = $brand.' '.$name;
        }

        // Extract date if provided in the item
        $itemDate = null;
        if (isset($item['date'])) {
            try {
                $itemDate = Carbon::parse($item['date']);
            } catch (\Exception $e) {
                // Use file date or current date
            }
        }

        if (! $name) {
            return null;
        }

        // Estimate quantity in grams based on caloric density
        // We'll use a reasonable estimation since your data doesn't have grams
        $estimatedQuantity = $this->estimateQuantityFromPortion($portion, $calories);

        return [
            'name' => trim($name),
            'quantity' => $estimatedQuantity,
            'calories' => (float) $calories,
            'protein' => (float) $protein,
            'carbs' => (float) $carbs,
            'fat' => (float) $fat,
            'date' => $itemDate ?? $date ?? Carbon::now(),
            'notes' => $portion ? "Original portion: {$portion}" : null,
        ];
    }

    private function estimateQuantityFromPortion(?string $portion = null, float $calories = 0): float
    {
        if (! $portion) {
            // Default estimation based on calories (rough approximation)
            return max(50, $calories * 0.5); // Assume ~2 calories per gram as baseline
        }

        $portion = strtolower($portion);

        // Extract numbers from portion descriptions
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:g|grams?|gram)/i', $portion, $matches)) {
            return (float) $matches[1];
        }

        // Common portion estimations in grams
        $portionEstimates = [
            'cup' => 240,
            'serving' => 100,
            'tbsp' => 15,
            'tsp' => 5,
            'can' => 400,
            'block' => 350,
            'beer' => 355,
            'handful' => 30,
            'tortilla' => 30,
        ];

        // Look for multipliers (e.g., "4 servings", "2 cups")
        $multiplier = 1;
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:servings?|cups?|tbsp|tsp|cans?|blocks?|beers?|handfuls?|tortillas?)/i', $portion, $matches)) {
            $multiplier = (float) $matches[1];
        }

        // Find base portion type
        foreach ($portionEstimates as $type => $grams) {
            if (strpos($portion, $type) !== false) {
                return $grams * $multiplier;
            }
        }

        // Fallback: estimate based on calories if no portion match
        return max(50, $calories * 0.5);
    }

    private function createFoodEntry(array $item, User $user): array
    {
        $createdFoodType = false;

        // Find or create food type
        $foodType = FoodType::where('name', $item['name'])->first();

        if (! $foodType) {
            // Calculate per 100g values
            $quantity = max($item['quantity'], 1); // Avoid division by zero
            $caloriesPer100g = ($item['calories'] / $quantity) * 100;
            $proteinPer100g = ($item['protein'] / $quantity) * 100;
            $carbsPer100g = ($item['carbs'] / $quantity) * 100;
            $fatPer100g = ($item['fat'] / $quantity) * 100;

            $foodType = FoodType::create([
                'name' => $item['name'],
                'description' => $item['notes'] ? 'Imported: '.$item['notes'] : 'Imported from old data',
                'calories_per_100g' => $caloriesPer100g,
                'protein_per_100g' => $proteinPer100g,
                'carbs_per_100g' => $carbsPer100g,
                'fat_per_100g' => $fatPer100g,
                'category' => 'Imported',
            ]);

            $createdFoodType = true;
        }

        // Create food entry
        Food::create([
            'user_id' => $user->id,
            'food_type_id' => $foodType->id,
            'quantity_grams' => $item['quantity'],
            'total_calories' => $item['calories'],
            'total_protein' => $item['protein'],
            'total_carbs' => $item['carbs'],
            'total_fat' => $item['fat'],
            'notes' => $item['notes'],
            'consumed_at' => $item['date'],
        ]);

        return ['created_food_type' => $createdFoodType];
    }
}
