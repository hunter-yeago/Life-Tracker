<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyNote extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getForUserAndDate(int $userId, Carbon $date): ?DailyNote
    {
        return static::where('user_id', $userId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
    }

    public static function upsertForUserAndDate(int $userId, Carbon $date, ?string $note): void
    {
        // Find existing record or create new one
        $dailyNote = static::where('user_id', $userId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
        
        if ($dailyNote) {
            $dailyNote->update(['note' => $note]);
        } else {
            static::create([
                'user_id' => $userId,
                'date' => $date->format('Y-m-d'),
                'note' => $note,
            ]);
        }
    }
}
