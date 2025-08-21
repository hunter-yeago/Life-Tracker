<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutType extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'muscle_group',
        'equipment_needed',
    ];

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }
}
