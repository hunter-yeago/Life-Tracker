<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $workoutStats = $this->getWorkoutStats($user, $thirtyDaysAgo);
        $nutritionStats = $this->getNutritionStats($user, $thirtyDaysAgo);
        $recentActivity = $this->getRecentActivity($user);

        return Inertia::render('Dashboard', [
            'workoutStats' => $workoutStats,
            'nutritionStats' => $nutritionStats,
            'recentActivity' => $recentActivity,
        ]);
    }

    private function getWorkoutStats($user, $thirtyDaysAgo): array
    {
        $workouts = $user->workouts()
            ->where('performed_at', '>=', $thirtyDaysAgo)
            ->with('workoutType')
            ->get();

        $workoutsByDay = $user->workouts()
            ->select(DB::raw('DATE(performed_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('performed_at', '>=', $thirtyDaysAgo)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $workoutsByType = $workouts->groupBy('workoutType.name')
            ->map(fn ($group) => $group->count());

        return [
            'totalWorkouts' => $workouts->count(),
            'workoutsByDay' => $workoutsByDay,
            'workoutsByType' => $workoutsByType,
            'totalSets' => $workouts->sum('sets'),
            'totalReps' => $workouts->sum('reps'),
        ];
    }

    private function getNutritionStats($user, $thirtyDaysAgo): array
    {
        $foods = $user->foods()
            ->where('consumed_at', '>=', $thirtyDaysAgo)
            ->get();

        $caloriesByDay = $user->foods()
            ->select(DB::raw('DATE(consumed_at) as date'), DB::raw('SUM(total_calories) as calories'))
            ->where('consumed_at', '>=', $thirtyDaysAgo)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $macrosByDay = $user->foods()
            ->select(
                DB::raw('DATE(consumed_at) as date'),
                DB::raw('SUM(total_protein) as protein'),
                DB::raw('SUM(total_carbs) as carbs'),
                DB::raw('SUM(total_fat) as fat')
            )
            ->where('consumed_at', '>=', $thirtyDaysAgo)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'totalCalories' => $foods->sum('total_calories'),
            'totalProtein' => $foods->sum('total_protein'),
            'totalCarbs' => $foods->sum('total_carbs'),
            'totalFat' => $foods->sum('total_fat'),
            'caloriesByDay' => $caloriesByDay,
            'macrosByDay' => $macrosByDay,
            'averageDailyCalories' => round($foods->sum('total_calories') / 30, 2),
        ];
    }

    private function getRecentActivity($user): array
    {
        $recentWorkouts = $user->workouts()
            ->with('workoutType')
            ->latest('performed_at')
            ->limit(5)
            ->get();

        $recentFoods = $user->foods()
            ->with('foodType')
            ->latest('consumed_at')
            ->limit(5)
            ->get();

        return [
            'workouts' => $recentWorkouts,
            'foods' => $recentFoods,
        ];
    }
}
