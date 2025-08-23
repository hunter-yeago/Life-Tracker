<?php

namespace App\Console\Commands;

use App\Models\FoodType;
use Illuminate\Console\Command;

class ConvertAugustFoodTypesToOneTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'food:convert-august-to-one-time {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert food types containing "august" in the name to one-time items';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Finding food types with "august" in the name...');

        // Find all food types that contain "august" (case insensitive) and are not already one-time items
        $foodTypes = FoodType::where('name', 'like', '%august%')
            ->where('is_one_time_item', false)
            ->get();

        if ($foodTypes->isEmpty()) {
            $this->info('No food types found with "august" in the name that are not already one-time items.');

            return Command::SUCCESS;
        }

        $this->info("Found {$foodTypes->count()} food type(s) to convert:");

        foreach ($foodTypes as $foodType) {
            $this->line("- {$foodType->name}");
        }

        if (! $this->option('force') && ! $this->confirm('Do you want to convert these food types to one-time items?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $updated = 0;
        foreach ($foodTypes as $foodType) {
            $foodType->update(['is_one_time_item' => true]);
            $updated++;
            $this->info("✓ Converted: {$foodType->name}");
        }

        $this->info("Successfully converted {$updated} food type(s) to one-time items.");

        return Command::SUCCESS;
    }
}
