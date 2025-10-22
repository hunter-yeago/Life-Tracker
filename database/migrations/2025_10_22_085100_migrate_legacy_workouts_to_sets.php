<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate legacy workout data to workout_sets table
        $workouts = DB::table('workouts')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('workout_sets')
                    ->whereColumn('workout_sets.workout_id', 'workouts.id');
            })
            ->get();

        foreach ($workouts as $workout) {
            // For cardio workouts with duration_minutes
            if ($workout->duration_minutes !== null) {
                DB::table('workout_sets')->insert([
                    'workout_id' => $workout->id,
                    'set_number' => 1,
                    'reps' => null,
                    'weight' => null,
                    'duration_seconds' => $workout->duration_minutes * 60,
                    'difficulty' => $workout->difficulty,
                    'completed' => true,
                    'notes' => null,
                    'created_at' => $workout->created_at,
                    'updated_at' => $workout->updated_at,
                ]);
            }
            // For strength workouts with sets/reps/weight
            elseif ($workout->sets !== null || $workout->reps !== null || $workout->weight !== null) {
                // Create a single set entry from the legacy aggregate data
                DB::table('workout_sets')->insert([
                    'workout_id' => $workout->id,
                    'set_number' => 1,
                    'reps' => $workout->reps,
                    'weight' => $workout->weight,
                    'duration_seconds' => null,
                    'difficulty' => $workout->difficulty,
                    'completed' => true,
                    'notes' => null,
                    'created_at' => $workout->created_at,
                    'updated_at' => $workout->updated_at,
                ]);
            }
        }

        // Drop legacy columns from workouts table
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn([
                'sets',
                'reps',
                'weight',
                'duration_minutes',
                'difficulty',
                'left_sets',
                'left_reps',
                'left_weight',
                'left_difficulty',
                'right_sets',
                'right_reps',
                'right_weight',
                'right_difficulty',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore legacy columns
        Schema::table('workouts', function (Blueprint $table) {
            $table->integer('sets')->nullable();
            $table->integer('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('duration_minutes', 8, 2)->nullable();
            $table->string('difficulty')->nullable();
            $table->integer('left_sets')->nullable();
            $table->integer('left_reps')->nullable();
            $table->decimal('left_weight', 8, 2)->nullable();
            $table->string('left_difficulty')->nullable();
            $table->integer('right_sets')->nullable();
            $table->integer('right_reps')->nullable();
            $table->decimal('right_weight', 8, 2)->nullable();
            $table->string('right_difficulty')->nullable();
        });

        // Migrate data back from workout_sets
        $workouts = DB::table('workouts')->get();

        foreach ($workouts as $workout) {
            $firstSet = DB::table('workout_sets')
                ->where('workout_id', $workout->id)
                ->orderBy('set_number')
                ->first();

            if ($firstSet) {
                DB::table('workouts')
                    ->where('id', $workout->id)
                    ->update([
                        'sets' => DB::table('workout_sets')->where('workout_id', $workout->id)->count(),
                        'reps' => $firstSet->reps,
                        'weight' => $firstSet->weight,
                        'duration_minutes' => $firstSet->duration_seconds ? $firstSet->duration_seconds / 60 : null,
                        'difficulty' => $firstSet->difficulty,
                    ]);
            }
        }
    }
};
