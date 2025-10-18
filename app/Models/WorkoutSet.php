<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSet extends Model
{
    /** @use HasFactory<\Database\Factories\WorkoutSetFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'set_number',
        'reps',
        'weight',
        'duration_seconds',
        'difficulty',
        'completed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'set_number' => 'integer',
            'reps' => 'integer',
            'weight' => 'decimal:2',
            'duration_seconds' => 'integer',
            'completed' => 'boolean',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function getDurationMinutesAttribute(): ?float
    {
        return $this->duration_seconds ? round($this->duration_seconds / 60, 2) : null;
    }

    public function setDurationMinutesAttribute(?float $minutes): void
    {
        $this->attributes['duration_seconds'] = $minutes ? (int) ($minutes * 60) : null;
    }
}
