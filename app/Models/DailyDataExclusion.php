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
        'food_notes',
        'workout_notes',
        'weight_notes',
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
        // Find existing record or create new one
        $exclusion = static::where('user_id', $userId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();

        if (!$exclusion) {
            $exclusion = static::create([
                'user_id' => $userId,
                'date' => $date->format('Y-m-d'),
                'exclude_food' => false,
                'exclude_workout' => false,
                'exclude_weight' => false,
            ]);
        }

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

    public static function setExclusionWithNote(int $userId, Carbon $date, string $dataType, bool $excluded, ?string $note = null): void
    {
        // Find existing record or create new one
        $exclusion = static::where('user_id', $userId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();

        if (!$exclusion) {
            $exclusion = static::create([
                'user_id' => $userId,
                'date' => $date->format('Y-m-d'),
                'exclude_food' => false,
                'exclude_workout' => false,
                'exclude_weight' => false,
            ]);
        }

        $columnName = "exclude_{$dataType}";
        $noteColumnName = "{$dataType}_notes";
        
        $exclusion->update([
            $columnName => $excluded,
            $noteColumnName => $excluded ? $note : null, // Clear note if not excluded
        ]);
    }

    public static function getExclusionNote(int $userId, Carbon $date, string $dataType): ?string
    {
        $exclusion = static::getForUserAndDate($userId, $date);
        if (! $exclusion) {
            return null;
        }

        $noteColumnName = "{$dataType}_notes";
        return $exclusion->$noteColumnName;
    }
}
