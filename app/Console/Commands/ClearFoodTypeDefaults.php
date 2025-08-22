<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FoodType;

class ClearFoodTypeDefaults extends Command
{
    protected $signature = 'clear:food-type-defaults';
    protected $description = 'Clear default serving sizes and weights from food types';

    public function handle()
    {
        $count = FoodType::query()->update([
            'serving_size' => null,
            'serving_weight_grams' => null,
        ]);
        
        $this->info("Cleared serving size defaults from {$count} food types.");
        return 0;
    }
}