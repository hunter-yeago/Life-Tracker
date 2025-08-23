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
        'sets',
        'reps',
        'weight',
        'distance',
        'both_sides',
        'difficulty',
        'notes',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'distance' => 'decimal:2',
            'both_sides' => 'boolean',
            'performed_at' => 'datetime',
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
