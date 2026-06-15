<?php

namespace App\Actions\Track;

use App\Models\DailyNote;
use App\Models\DailyWeight;
use App\Models\User;
use Carbon\Carbon;

use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class ShowDailySummary
{
    public static function handle(User $user, Carbon $date): void
    {
        note("Date: {$date->format('l, F j, Y')}");

        $weight = DailyWeight::getForUserAndDate($user->id, $date);
        $dailyNote = DailyNote::getForUserAndDate($user->id, $date);

        note('Weight: '.($weight ? "{$weight->weight} lbs".($weight->notes ? " ({$weight->notes})" : '') : 'not recorded'));
        note('Note: '.($dailyNote?->note ?: 'none'));

        $foods = $user->foods()
            ->with('foodType')
            ->whereDate('consumed_at', $date)
            ->orderBy('consumed_at')
            ->get();

        if ($foods->isEmpty()) {
            note('Food: none logged');
        } else {
            $totals = [
                'calories' => $foods->sum('total_calories'),
                'protein' => $foods->sum('total_protein'),
                'carbs' => $foods->sum('total_carbs'),
                'fat' => $foods->sum('total_fat'),
            ];

            note(sprintf(
                'Food totals: %.0f cal, %.0fg protein, %.0fg carbs, %.0fg fat',
                $totals['calories'],
                $totals['protein'],
                $totals['carbs'],
                $totals['fat'],
            ));

            table(
                ['Food', 'Servings', 'Calories'],
                $foods->map(fn ($food) => [
                    $food->foodType->name,
                    (string) $food->servings,
                    (string) $food->total_calories,
                ])->all(),
            );
        }

        $workouts = $user->workouts()
            ->with('workoutType')
            ->whereDate('performed_at', $date)
            ->get();

        if ($workouts->isEmpty()) {
            note('Workouts: none logged');
        } else {
            table(
                ['Exercise', 'Sets'],
                $workouts->map(fn ($workout) => [
                    $workout->workoutType->name,
                    (string) $workout->sets()->count(),
                ])->all(),
            );
        }
    }
}
