<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyDataExclusion extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'exclude_food',
        'exclude_workout',
        'exclude_weight',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'exclude_food' => 'boolean',
            'exclude_workout' => 'boolean',
            'exclude_weight' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForUserAndDate(int $userId, Carbon $date): ?DailyDataExclusion
    {
        return static::where('user_id', $userId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
    }

    public static function toggleExclusion(int $userId, Carbon $date, string $dataType): void
    {
        $dateString = $date->format('Y-m-d');
        
        $exclusion = static::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $dateString,
            ],
            [
                'exclude_food' => false,
                'exclude_workout' => false,
                'exclude_weight' => false,
            ]
        );

        $columnName = "exclude_{$dataType}";
        $exclusion->update([
            $columnName => ! $exclusion->$columnName,
        ]);
    }

    public static function isExcluded(int $userId, Carbon $date, string $dataType): bool
    {
        $exclusion = static::getForUserAndDate($userId, $date);
        if (! $exclusion) {
            return false;
        }

        $columnName = "exclude_{$dataType}";

        return $exclusion->$columnName;
    }
}
