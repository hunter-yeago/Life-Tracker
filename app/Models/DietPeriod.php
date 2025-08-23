<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPeriod extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'phase_type',
        'name',
        'notes',
        'target_calories',
        'target_protein',
        'target_carbs',
        'target_fat',
        'starting_weight',
        'target_weight',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'target_calories' => 'decimal:2',
            'target_protein' => 'decimal:2',
            'target_carbs' => 'decimal:2',
            'target_fat' => 'decimal:2',
            'starting_weight' => 'decimal:2',
            'target_weight' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(?Carbon $date = null): bool
    {
        $date = $date ?? now();

        return $date->greaterThanOrEqualTo($this->start_date) &&
               ($this->end_date === null || $date->lessThanOrEqualTo($this->end_date));
    }

    public function isOngoing(): bool
    {
        return $this->end_date === null;
    }

    public function getDurationInDays(): ?int
    {
        if ($this->end_date === null) {
            return null; // ongoing
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public static function getActivePeriodForDate(int $userId, Carbon $date): ?self
    {
        return self::where('user_id', $userId)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
    }

    public static function getCurrentPeriod(int $userId): ?self
    {
        return self::getActivePeriodForDate($userId, now());
    }
}
