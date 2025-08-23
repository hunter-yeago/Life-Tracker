<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyWeight extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'weight',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'weight' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForUserAndDate(int $userId, Carbon $date): ?DailyWeight
    {
        return self::where('user_id', $userId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
    }

    public static function upsertForUserAndDate(int $userId, Carbon $date, float $weight, ?string $notes = null): DailyWeight
    {
        $dateString = $date->format('Y-m-d');

        // Try to find existing record
        $existing = static::where('user_id', $userId)
            ->whereDate('date', $dateString)
            ->first();

        if ($existing) {
            $existing->update([
                'weight' => $weight,
                'notes' => $notes,
            ]);

            return $existing;
        }

        // Create new record
        return static::create([
            'user_id' => $userId,
            'date' => $dateString,
            'weight' => $weight,
            'notes' => $notes,
        ]);
    }
}
