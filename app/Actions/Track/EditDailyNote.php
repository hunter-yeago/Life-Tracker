<?php

namespace App\Actions\Track;

use App\Models\DailyNote;
use App\Models\User;
use Carbon\Carbon;

use function Laravel\Prompts\info;
use function Laravel\Prompts\textarea;

class EditDailyNote
{
    public static function handle(User $user, Carbon $date): void
    {
        $existing = DailyNote::getForUserAndDate($user->id, $date);

        $note = textarea(
            label: 'Daily note',
            default: $existing?->note ?? '',
            validate: fn (string $value) => strlen($value) > 2000 ? 'Note must be 2000 characters or fewer.' : null,
        );

        DailyNote::upsertForUserAndDate($user->id, $date, $note !== '' ? $note : null);

        info('Daily note saved.');
    }
}
