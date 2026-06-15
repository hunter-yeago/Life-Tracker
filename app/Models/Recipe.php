<?php

namespace App\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    protected $fillable = [
        'food_type_id',
        'name',
        'description',
        'instructions',
        'servings',
    ];

    public function foodType(): BelongsTo
    {
        return $this->belongsTo(FoodType::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(RecipeSource::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort_order');
    }
}
