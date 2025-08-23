<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workout extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workout_type_id',
        'duration_minutes',
        'calories_burned',
        'intensity',
        'notes',
        'workout_date',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'calories_burned' => 'decimal:2',
            'workout_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutType(): BelongsTo
    {
        return $this->belongsTo(WorkoutType::class);
    }
}
