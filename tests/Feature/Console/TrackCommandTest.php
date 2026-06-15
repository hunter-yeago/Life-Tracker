<?php

namespace Tests\Feature\Console;

use App\Models\DailyNote;
use App\Models\DailyWeight;
use App\Models\Food;
use App\Models\FoodType;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private const MENU_OPTIONS = [
        'food' => 'Log food',
        'workout' => 'Log workout',
        'weight' => 'Set/update weight',
        'note' => 'Edit daily note',
        'date' => 'Change date',
        'exit' => 'Exit',
    ];

    public function test_it_sets_the_weight_for_the_day(): void
    {
        $user = User::factory()->create();

        $this->artisan('track', ['--user' => $user->id, '--date' => '2026-06-15'])
            ->expectsChoice('What would you like to do?', 'weight', self::MENU_OPTIONS)
            ->expectsQuestion('Weight', '180')
            ->expectsQuestion('Notes (optional)', '')
            ->expectsChoice('What would you like to do?', 'exit', self::MENU_OPTIONS)
            ->assertExitCode(0);

        $weight = DailyWeight::getForUserAndDate($user->id, now()->parse('2026-06-15'));

        $this->assertNotNull($weight);
        $this->assertEquals(180.0, (float) $weight->weight);
    }

    public function test_it_edits_the_daily_note(): void
    {
        $user = User::factory()->create();

        $this->artisan('track', ['--user' => $user->id, '--date' => '2026-06-15'])
            ->expectsChoice('What would you like to do?', 'note', self::MENU_OPTIONS)
            ->expectsQuestion('Daily note', 'Felt great today')
            ->expectsChoice('What would you like to do?', 'exit', self::MENU_OPTIONS)
            ->assertExitCode(0);

        $note = DailyNote::getForUserAndDate($user->id, now()->parse('2026-06-15'));

        $this->assertNotNull($note);
        $this->assertEquals('Felt great today', $note->note);
    }

    public function test_it_logs_food_against_an_existing_food_type(): void
    {
        $user = User::factory()->create();

        $foodType = FoodType::create([
            'name' => 'Chicken Breast',
            'description' => null,
            'serving_size' => '100g',
            'serving_weight_grams' => 100,
            'calories_per_serving' => 200,
            'protein_per_serving' => 40,
            'carbs_per_serving' => 0,
            'fat_per_serving' => 4,
            'category' => 'Food',
            'is_one_time_item' => false,
        ]);

        $searchResults = [
            '__create_new__' => '+ Create new food type',
            $foodType->id => "Chicken Breast ({$foodType->calories_per_serving} cal / {$foodType->serving_size})",
        ];

        $this->artisan('track', ['--user' => $user->id, '--date' => '2026-06-15'])
            ->expectsChoice('What would you like to do?', 'food', self::MENU_OPTIONS)
            ->expectsSearch('Search for a food', $foodType->id, 'Chicken', $searchResults)
            ->expectsQuestion('Servings', '2')
            ->expectsQuestion('Notes (optional)', '')
            ->expectsChoice('What would you like to do?', 'exit', self::MENU_OPTIONS)
            ->assertExitCode(0);

        $food = Food::first();

        $this->assertNotNull($food);
        $this->assertEquals($foodType->id, $food->food_type_id);
        $this->assertEquals(2.0, (float) $food->servings);
        $this->assertEquals(400.0, (float) $food->total_calories);
        $this->assertEquals(80.0, (float) $food->total_protein);
        $this->assertEquals(0.0, (float) $food->total_carbs);
        $this->assertEquals(8.0, (float) $food->total_fat);
        $this->assertEquals('2026-06-15 12:00:00', $food->consumed_at->format('Y-m-d H:i:s'));
    }

    public function test_it_logs_a_workout_and_appends_a_second_set_on_the_same_day(): void
    {
        $user = User::factory()->create();

        $workoutType = WorkoutType::create([
            'name' => 'Bench Press',
            'description' => null,
            'muscle_group' => 'Chest',
            'equipment_needed' => 'Barbell',
            'sides' => 'none',
        ]);

        $searchResults = [
            '__create_new__' => '+ Create new exercise',
            $workoutType->id => "Bench Press ({$workoutType->muscle_group})",
        ];

        $difficultyOptions = [
            'none' => '(none)',
            'easy' => 'Easy',
            'hard' => 'Hard',
            'really_hard' => 'Really hard',
            'almost_fail' => 'Almost fail',
            'fail' => 'Fail',
        ];

        // First session: creates the workout and its first set.
        $this->artisan('track', ['--user' => $user->id, '--date' => '2026-06-15'])
            ->expectsChoice('What would you like to do?', 'workout', self::MENU_OPTIONS)
            ->expectsSearch('Search for an exercise', $workoutType->id, 'Bench', $searchResults)
            ->expectsQuestion('Reps (optional)', '10')
            ->expectsQuestion('Weight (optional)', '135')
            ->expectsQuestion('Duration in minutes (optional)', '')
            ->expectsChoice('Difficulty', 'none', $difficultyOptions)
            ->expectsConfirmation('Completed?', 'yes')
            ->expectsQuestion('Set notes (optional)', '')
            ->expectsConfirmation('Add another set?', 'no')
            ->expectsQuestion('Workout notes (optional)', '')
            ->expectsChoice('What would you like to do?', 'exit', self::MENU_OPTIONS)
            ->assertExitCode(0);

        $workout = Workout::first();
        $this->assertNotNull($workout);
        $this->assertEquals(1, $workout->sets()->count());

        // Second session, same day and exercise: appends a second set to the existing workout.
        $this->artisan('track', ['--user' => $user->id, '--date' => '2026-06-15'])
            ->expectsChoice('What would you like to do?', 'workout', self::MENU_OPTIONS)
            ->expectsSearch('Search for an exercise', $workoutType->id, 'Bench', $searchResults)
            ->expectsQuestion('Reps (optional)', '8')
            ->expectsQuestion('Weight (optional)', '145')
            ->expectsQuestion('Duration in minutes (optional)', '')
            ->expectsChoice('Difficulty', 'none', $difficultyOptions)
            ->expectsConfirmation('Completed?', 'yes')
            ->expectsQuestion('Set notes (optional)', '')
            ->expectsConfirmation('Add another set?', 'no')
            ->expectsChoice('What would you like to do?', 'exit', self::MENU_OPTIONS)
            ->assertExitCode(0);

        $workout->refresh();
        $this->assertEquals(1, Workout::count());
        $this->assertEquals(2, $workout->sets()->count());

        $secondSet = $workout->sets()->where('set_number', 2)->first();
        $this->assertNotNull($secondSet);
        $this->assertEquals(8, $secondSet->reps);
        $this->assertEquals(145.0, (float) $secondSet->weight);
    }
}
