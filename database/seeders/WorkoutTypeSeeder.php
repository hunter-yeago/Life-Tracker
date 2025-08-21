<?php

namespace Database\Seeders;

use App\Models\WorkoutType;
use Illuminate\Database\Seeder;

class WorkoutTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workoutTypes = [
            [
                'name' => 'Bench Press',
                'description' => 'Upper body exercise targeting chest, shoulders, and triceps',
                'muscle_group' => 'Chest',
                'equipment_needed' => 'barbell',
            ],
            [
                'name' => 'Squats',
                'description' => 'Lower body compound movement targeting quadriceps, glutes, and hamstrings',
                'muscle_group' => 'Legs',
                'equipment_needed' => 'barbell',
            ],
            [
                'name' => 'Deadlift',
                'description' => 'Full body compound exercise targeting posterior chain',
                'muscle_group' => 'Back',
                'equipment_needed' => 'barbell',
            ],
            [
                'name' => 'Pull-ups',
                'description' => 'Upper body exercise targeting lats, rhomboids, and biceps',
                'muscle_group' => 'Back',
                'equipment_needed' => 'bodyweight',
            ],
            [
                'name' => 'Push-ups',
                'description' => 'Upper body bodyweight exercise targeting chest, shoulders, and triceps',
                'muscle_group' => 'Chest',
                'equipment_needed' => 'bodyweight',
            ],
            [
                'name' => 'Running',
                'description' => 'Cardiovascular exercise for endurance and fitness',
                'muscle_group' => 'Cardio',
                'equipment_needed' => 'none',
            ],
            [
                'name' => 'Bicep Curls',
                'description' => 'Isolation exercise targeting biceps',
                'muscle_group' => 'Arms',
                'equipment_needed' => 'dumbbells',
            ],
            [
                'name' => 'Shoulder Press',
                'description' => 'Overhead pressing movement targeting shoulders',
                'muscle_group' => 'Shoulders',
                'equipment_needed' => 'dumbbells',
            ],
        ];

        foreach ($workoutTypes as $workoutType) {
            WorkoutType::create($workoutType);
        }
    }
}
