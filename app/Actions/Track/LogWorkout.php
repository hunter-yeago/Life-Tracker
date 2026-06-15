<?php

namespace App\Actions\Track;

use App\Models\User;
use App\Models\WorkoutType;
use Carbon\Carbon;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class LogWorkout
{
    private const CREATE_NEW = '__create_new__';

    private const DIFFICULTY_OPTIONS = [
        'none' => '(none)',
        'easy' => 'Easy',
        'hard' => 'Hard',
        'really_hard' => 'Really hard',
        'almost_fail' => 'Almost fail',
        'fail' => 'Fail',
    ];

    public static function handle(User $user, Carbon $date): void
    {
        $workoutTypeId = search(
            label: 'Search for an exercise',
            options: fn (string $value) => self::searchOptions($value),
        );

        $workoutType = $workoutTypeId === self::CREATE_NEW
            ? self::createWorkoutType()
            : WorkoutType::findOrFail($workoutTypeId);

        if ($workoutType->sides !== 'none') {
            note("This exercise tracks {$workoutType->sides} sides — log left and right sets separately, noting the side in each set's notes.");
        }

        $sets = self::collectSets();

        $existingWorkout = $user->workouts()
            ->where('workout_type_id', $workoutType->id)
            ->whereDate('performed_at', $date)
            ->first();

        if ($existingWorkout) {
            $nextSetNumber = ($existingWorkout->sets()->max('set_number') ?? 0) + 1;

            foreach ($sets as $setData) {
                $setData['set_number'] = $nextSetNumber++;
                $existingWorkout->sets()->create($setData);
            }

            $workout = $existingWorkout;
        } else {
            $workoutNotes = textarea(
                label: 'Workout notes (optional)',
                validate: fn (string $value) => strlen($value) > 1000 ? 'Notes must be 1000 characters or fewer.' : null,
            );

            $workout = $user->workouts()->create([
                'workout_type_id' => $workoutType->id,
                'notes' => $workoutNotes !== '' ? $workoutNotes : null,
                'performed_at' => $date->copy()->setTime(12, 0, 0),
            ]);

            $setNumber = 1;
            foreach ($sets as $setData) {
                $setData['set_number'] = $setNumber++;
                $workout->sets()->create($setData);
            }
        }

        info('Logged '.count($sets)." set(s) of {$workoutType->name}.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function collectSets(): array
    {
        $sets = [];

        do {
            $reps = text(
                label: 'Reps (optional)',
                validate: fn (string $value) => match (true) {
                    $value === '' => null,
                    ! ctype_digit($value) || (int) $value < 1 => 'Reps must be a whole number of at least 1.',
                    default => null,
                },
            );

            $weight = text(
                label: 'Weight (optional)',
                validate: fn (string $value) => match (true) {
                    $value === '' => null,
                    ! is_numeric($value) || (float) $value < 0 => 'Weight must be 0 or greater.',
                    default => null,
                },
            );

            $durationMinutes = text(
                label: 'Duration in minutes (optional)',
                validate: fn (string $value) => match (true) {
                    $value === '' => null,
                    ! is_numeric($value) || (float) $value <= 0 => 'Duration must be greater than 0.',
                    default => null,
                },
            );

            $difficulty = select(
                label: 'Difficulty',
                options: self::DIFFICULTY_OPTIONS,
                default: 'none',
            );

            $completed = confirm('Completed?', default: true);

            $setNotes = textarea(
                label: 'Set notes (optional)',
                validate: fn (string $value) => strlen($value) > 500 ? 'Notes must be 500 characters or fewer.' : null,
            );

            $sets[] = [
                'reps' => $reps !== '' ? (int) $reps : null,
                'weight' => $weight !== '' ? (float) $weight : null,
                'duration_seconds' => $durationMinutes !== '' ? (int) round((float) $durationMinutes * 60) : null,
                'difficulty' => $difficulty !== 'none' ? $difficulty : null,
                'completed' => $completed,
                'notes' => $setNotes !== '' ? $setNotes : null,
            ];
        } while (confirm('Add another set?', default: true));

        return $sets;
    }

    /**
     * @return array<int|string, string>
     */
    private static function searchOptions(string $value): array
    {
        $workoutTypes = WorkoutType::query()
            ->when($value !== '', fn ($query) => $query->where('name', 'like', "%{$value}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();

        $options = $workoutTypes->mapWithKeys(fn (WorkoutType $workoutType) => [
            $workoutType->id => "{$workoutType->name} ({$workoutType->muscle_group})",
        ])->all();

        return [self::CREATE_NEW => '+ Create new exercise'] + $options;
    }

    private static function createWorkoutType(): WorkoutType
    {
        $name = text(
            label: 'Name',
            required: 'Name is required.',
        );

        while (WorkoutType::where('name', $name)->exists()) {
            $name = text(
                label: "An exercise named \"{$name}\" already exists. Enter a different name",
                required: 'Name is required.',
            );
        }

        $muscleGroup = text(
            label: 'Muscle group',
            required: 'Muscle group is required.',
        );

        $equipmentNeeded = text(label: 'Equipment needed (optional)');

        $sides = select(
            label: 'Sides',
            options: [
                'none' => 'None',
                'both' => 'Both',
                'separate' => 'Separate left/right',
            ],
            default: 'none',
        );

        $description = text(label: 'Description (optional)');

        return WorkoutType::create([
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'muscle_group' => $muscleGroup,
            'equipment_needed' => $equipmentNeeded !== '' ? $equipmentNeeded : null,
            'sides' => $sides,
        ]);
    }
}
