<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Models\FoodType;
use Illuminate\Console\Command;

class CleanupFoodTypesCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'food-types:cleanup-catalog {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove leftover test/duplicate food types, fix naming, re-flag mislabeled one-off entries, and categorize reusable food types';

    /**
     * @var array<string, list<string>>
     */
    private const CATEGORY_ASSIGNMENTS = [
        'Protein' => [
            'Black Beans',
            'Chickpeas (1 cup)',
            'Protein Bar (Barebell)',
            'Protein Bar - Met-Rx',
            'Protein Powder - Optimum Nutrition - Chocolate',
            'Protein bar (Clif)',
            'Refried Beans',
            'Tofu',
        ],
        'Carbohydrates' => [
            'Bagel (both halves)',
            'Butternut Whole Grain Wheat Bread',
            'Oats (Quaker) (1/2 cup)',
            'Toasted Bread',
            'Tomato Polenta',
            'Tortilla (60 cal)',
            'Tortilla (110cal)',
            'Tortilla (40 cal)',
            'White Rice (1 cup)',
        ],
        'Vegetables' => [
            'Baby Tomatoes',
            'Bell Peppers (1 cup)',
            'Broccoli (1 cup)',
            'Carrots (1 cup)',
            'Diced Tomatoes',
            'Green Chilis',
            'Kimchi',
            'Onion (1 onion)',
            'Spring Mix',
            'Tomato (small)',
            'Vegetable Soup - Progresso',
        ],
        'Fruits' => [
            'Apple',
            'Banana',
            'Berry Mix (blue, rasp, black) - (1 cup)',
            'Orange',
        ],
        'Fats' => [
            'Avo Plant Butter',
            'Avocado',
            'Flax Seed (1 tbsp)',
            'Olive Oil',
            'Olives',
            'Peanut Butter (2 Tbsp)',
            'Plant Butter',
        ],
        'Condiments' => [
            'Balsamic Vinegar Dressing',
            'Buffalo Sauce',
            'Chocolate Sauce (1 tbsp)',
            'Chunky Salsa (Organics)',
            'Enchilada Sauce',
            'Hummus',
            'Lemon Vinagrette',
            'Nutritional Yeast (Bobs Red Mill) (2 tbsp)',
            'Peanut Dressing (Thai Style)',
            'Salad Dressing - Panera Poppy Seed (2 Tbsp)',
            'Soy Sauce',
            'Vegetable Broth (1 cup)',
        ],
        'Drinks' => [
            'Alcohol',
            'Almond Milk',
            'NA beer',
            'Oat Milk',
        ],
        'Snacks' => [
            'Peroshki',
            'Sarpinos cheese pizza slice',
        ],
        'Recipes' => [
            'Berry Overnight Oats',
            'Tofu Veggie Power Bowl',
            'Romesco Seitan Polenta Bowl',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will modify the food_types catalog (delete test row, merge Broccoli duplicates, fix naming, re-flag mislabeled rows, and assign categories). Continue?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->removeTestRow();
        $this->fixTortillaTypo();
        $this->mergeDuplicateBroccoli();
        $this->reflagMislabeledOneOffs();
        $this->assignCategories();

        $this->info('Food types catalog cleanup complete.');

        return Command::SUCCESS;
    }

    private function removeTestRow(): void
    {
        $testFoodType = FoodType::where('name', 'test')->first();

        if (! $testFoodType) {
            $this->info('No "test" food type found, skipping.');

            return;
        }

        $testFoodType->delete();
        $this->info('Deleted leftover "test" food type.');
    }

    private function fixTortillaTypo(): void
    {
        $tortila = FoodType::where('name', 'Tortila (60 cal)')->first();

        if (! $tortila) {
            $this->info('No "Tortila (60 cal)" food type found, skipping.');

            return;
        }

        $tortila->update(['name' => 'Tortilla (60 cal)']);
        $this->info('Renamed "Tortila (60 cal)" to "Tortilla (60 cal)".');
    }

    private function mergeDuplicateBroccoli(): void
    {
        $broccoli = FoodType::where('name', 'Broccoli')->first();
        $broccoliCup = FoodType::where('name', 'Broccoli (1 cup)')->first();

        if (! $broccoli || ! $broccoliCup) {
            $this->info('Broccoli duplicates not found, skipping merge.');

            return;
        }

        Food::where('food_type_id', $broccoli->id)->update(['food_type_id' => $broccoliCup->id]);
        $broccoli->delete();
        $this->info('Merged "Broccoli" into "Broccoli (1 cup)".');
    }

    private function reflagMislabeledOneOffs(): void
    {
        $names = [
            'Cracked Farro Salad - Ema - Sep 10th lunch',
            'Ziyard Large Fava Beans + Sanabel Pita Bread + Ziyard Large Fava Beans - june 9th (Aug 25, 1:31 PM)',
        ];

        foreach ($names as $name) {
            $foodType = FoodType::where('name', $name)->first();

            if (! $foodType) {
                $this->info("Food type \"{$name}\" not found, skipping re-flag.");

                continue;
            }

            $foodType->update(['is_one_time_item' => true]);
            $this->info("Re-flagged \"{$name}\" as a one-time item.");
        }
    }

    private function assignCategories(): void
    {
        foreach (self::CATEGORY_ASSIGNMENTS as $category => $names) {
            $updated = FoodType::whereIn('name', $names)->update(['category' => $category]);
            $this->info("Assigned category \"{$category}\" to {$updated} food type(s).");
        }
    }
}
