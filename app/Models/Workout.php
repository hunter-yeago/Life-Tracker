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
        'difficulty',
        'left_sets',
        'left_reps',
        'left_weight',
        'left_difficulty',
        'right_sets',
        'right_reps',
        'right_weight',
        'right_difficulty',
        'notes',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'sets' => 'integer',
            'reps' => 'integer',
            'weight' => 'decimal:2',
            'distance' => 'decimal:2',
            'left_sets' => 'integer',
            'left_reps' => 'integer',
            'left_weight' => 'decimal:2',
            'right_sets' => 'integer',
            'right_reps' => 'integer',
            'right_weight' => 'decimal:2',
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
