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
        $month = request('month', Carbon::now()->format('Y-m'));
        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $nutritionStats = $this->getNutritionStats($user, $startOfMonth, $endOfMonth);
        $weightStats = $this->getWeightStats($user, $startOfMonth, $endOfMonth);

        return Inertia::render('Dashboard', [
            'nutritionStats' => $nutritionStats,
            'weightStats' => $weightStats,
            'currentMonth' => $month,
        ]);
    }


    private function getNutritionStats($user, $startOfMonth, $endOfMonth): array
    {
        $foods = $user->foods()
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->get();

        $caloriesByDay = $user->foods()
            ->select(DB::raw('DATE(consumed_at) as date'), DB::raw('SUM(total_calories) as calories'))
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $daysInMonth = $startOfMonth->daysInMonth;
        $averageCalories = $foods->count() > 0 ? round($foods->sum('total_calories') / $daysInMonth, 2) : 0;

        return [
            'totalCalories' => $foods->sum('total_calories'),
            'totalProtein' => $foods->sum('total_protein'),
            'totalCarbs' => $foods->sum('total_carbs'),
            'totalFat' => $foods->sum('total_fat'),
            'caloriesByDay' => $caloriesByDay,
            'averageDailyCalories' => $averageCalories,
        ];
    }

    private function getWeightStats($user, $startOfMonth, $endOfMonth): array
    {
        $weightData = $user->foods()
            ->select(DB::raw('DATE(consumed_at) as date'), DB::raw('AVG(weight) as weight'))
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->whereNotNull('weight')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'weightByDay' => $weightData,
        ];
    }
}
