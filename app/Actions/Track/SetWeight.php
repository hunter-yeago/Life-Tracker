<?php

namespace App\Actions\Track;

use App\Models\DailyWeight;
use App\Models\User;
use Carbon\Carbon;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class SetWeight
{
    public static function handle(User $user, Carbon $date): void
    {
        $existing = DailyWeight::getForUserAndDate($user->id, $date);

        $weight = text(
            label: 'Weight',
            default: $existing ? (string) $existing->weight : '',
            validate: fn (string $value) => match (true) {
                ! is_numeric($value) => 'Weight must be a number.',
                (float) $value < 0 || (float) $value > 9999 => 'Weight must be between 0 and 9999.',
                default => null,
            },
        );

        $notes = textarea(
            label: 'Notes (optional)',
            default: $existing?->notes ?? '',
            validate: fn (string $value) => strlen($value) > 1000 ? 'Notes must be 1000 characters or fewer.' : null,
        );

        DailyWeight::upsertForUserAndDate($user->id, $date, (float) $weight, $notes !== '' ? $notes : null);

        info("Weight for {$date->format('Y-m-d')} set to {$weight}.");
    }
}
