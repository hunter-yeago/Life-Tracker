<?php

namespace App\Actions\Track;

use App\Models\FoodType;
use App\Models\User;
use Carbon\Carbon;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class LogFood
{
    private const CREATE_NEW = '__create_new__';

    public static function handle(User $user, Carbon $date): void
    {
        $foodTypeId = search(
            label: 'Search for a food',
            options: fn (string $value) => self::searchOptions($value),
        );

        $foodType = $foodTypeId === self::CREATE_NEW
            ? self::createFoodType()
            : FoodType::findOrFail($foodTypeId);

        $servings = (float) text(
            label: 'Servings',
            default: '1',
            validate: fn (string $value) => match (true) {
                ! is_numeric($value) => 'Servings must be a number.',
                (float) $value < 0.01 => 'Servings must be at least 0.01.',
                default => null,
            },
        );

        $notes = textarea(
            label: 'Notes (optional)',
            validate: fn (string $value) => strlen($value) > 1000 ? 'Notes must be 1000 characters or fewer.' : null,
        );

        $user->foods()->create([
            'food_type_id' => $foodType->id,
            'servings' => $servings,
            'quantity_grams' => null,
            'total_calories' => $foodType->calories_per_serving * $servings,
            'total_protein' => $foodType->protein_per_serving * $servings,
            'total_carbs' => $foodType->carbs_per_serving * $servings,
            'total_fat' => $foodType->fat_per_serving * $servings,
            'notes' => $notes !== '' ? $notes : null,
            'consumed_at' => $date->copy()->setTime(12, 0, 0),
        ]);

        info("Logged {$servings} serving(s) of {$foodType->name}.");
    }

    /**
     * @return array<int|string, string>
     */
    private static function searchOptions(string $value): array
    {
        $foodTypes = FoodType::query()
            ->where(function ($query) {
                $query->where('is_one_time_item', false)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('is_one_time_item', true)
                            ->whereDoesntHave('foods');
                    });
            })
            ->when($value !== '', fn ($query) => $query->where('name', 'like', "%{$value}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();

        $options = $foodTypes->mapWithKeys(fn (FoodType $foodType) => [
            $foodType->id => "{$foodType->name} ({$foodType->calories_per_serving} cal / {$foodType->serving_size})",
        ])->all();

        return [self::CREATE_NEW => '+ Create new food type'] + $options;
    }

    private static function createFoodType(): FoodType
    {
        $name = text(
            label: 'Name',
            required: 'Name is required.',
        );

        $isOneTimeItem = confirm('Is this a one-time item?', default: false);

        if ($isOneTimeItem) {
            $name .= ' ('.now()->format('M j, g:i A').')';
        } else {
            while (FoodType::where('name', $name)->exists()) {
                $name = text(
                    label: "A food type named \"{$name}\" already exists. Enter a different name",
                    required: 'Name is required.',
                );
            }
        }

        $numericValidator = fn (string $label) => fn (string $value) => match (true) {
            ! is_numeric($value) => "{$label} must be a number.",
            (float) $value < 0 => "{$label} must be 0 or greater.",
            default => null,
        };

        $calories = (float) text(label: 'Calories per serving', validate: $numericValidator('Calories'));
        $protein = (float) text(label: 'Protein per serving (g)', validate: $numericValidator('Protein'));
        $carbs = (float) text(label: 'Carbs per serving (g)', validate: $numericValidator('Carbs'));
        $fat = (float) text(label: 'Fat per serving (g)', validate: $numericValidator('Fat'));

        $description = text(label: 'Description (optional)');

        return FoodType::create([
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'serving_size' => '1 serving',
            'serving_weight_grams' => null,
            'calories_per_serving' => $calories,
            'protein_per_serving' => $protein,
            'carbs_per_serving' => $carbs,
            'fat_per_serving' => $fat,
            'category' => 'Food',
            'is_one_time_item' => $isOneTimeItem,
        ]);
    }
}
