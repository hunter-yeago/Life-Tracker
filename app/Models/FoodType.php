<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Artisan;

class FoodType extends Model
{
    /** @use HasFactory<\Database\Factories\FoodTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'serving_size',
        'serving_weight_grams',
        'calories_per_serving',
        'protein_per_serving',
        'carbs_per_serving',
        'fat_per_serving',
        'category',
        'is_one_time_item',
    ];

    protected function casts(): array
    {
        return [
            'serving_weight_grams' => 'decimal:2',
            'calories_per_serving' => 'decimal:2',
            'protein_per_serving' => 'decimal:2',
            'carbs_per_serving' => 'decimal:2',
            'fat_per_serving' => 'decimal:2',
            'is_one_time_item' => 'boolean',
        ];
    }

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    public function scopeRegularItems($query)
    {
        return $query->where('is_one_time_item', false);
    }

    public function scopeOneTimeItems($query)
    {
        return $query->where('is_one_time_item', true);
    }

    protected static function booted(): void
    {
        static::updated(function (FoodType $foodType) {
            // Check if any nutritional values were changed
            $nutritionalFields = [
                'calories_per_serving',
                'protein_per_serving',
                'carbs_per_serving',
                'fat_per_serving'
            ];

            $hasNutritionalChanges = false;
            foreach ($nutritionalFields as $field) {
                if ($foodType->wasChanged($field)) {
                    $hasNutritionalChanges = true;
                    break;
                }
            }

            if ($hasNutritionalChanges) {
                // Recalculate nutritional values for all food entries of this type
                \Log::info("Recalculating nutrition for food type: " . $foodType->name);
                
                $exitCode = Artisan::call('food:recalculate-nutrition', [
                    '--food-type-id' => $foodType->id,
                ]);
                
                \Log::info("Recalculation completed with exit code: " . $exitCode);
            }
        });
    }
}
