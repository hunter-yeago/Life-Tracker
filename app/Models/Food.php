<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Food extends Model
{
    /** @use HasFactory<\Database\Factories\FoodFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'food_type_id',
        'quantity_grams',
        'total_calories',
        'total_protein',
        'total_carbs',
        'total_fat',
        'notes',
        'consumed_at',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'quantity_grams' => 'decimal:2',
            'total_calories' => 'decimal:2',
            'total_protein' => 'decimal:2',
            'total_carbs' => 'decimal:2',
            'total_fat' => 'decimal:2',
            'consumed_at' => 'datetime',
            'weight' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function foodType(): BelongsTo
    {
        return $this->belongsTo(FoodType::class);
    }
}
