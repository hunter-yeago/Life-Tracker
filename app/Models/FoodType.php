<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodType extends Model
{
    /** @use HasFactory<\Database\Factories\FoodTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'calories_per_100g',
        'protein_per_100g',
        'carbs_per_100g',
        'fat_per_100g',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'calories_per_100g' => 'decimal:2',
            'protein_per_100g' => 'decimal:2',
            'carbs_per_100g' => 'decimal:2',
            'fat_per_100g' => 'decimal:2',
        ];
    }

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }
}
