<?php

namespace App\Http\Controllers;

use App\Models\DailyDataExclusion;
use App\Models\DietPeriod;
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
        $dietPeriods = $this->getDietPeriods($user, $startOfMonth, $endOfMonth);

        return Inertia::render('Dashboard', [
            'nutritionStats' => $nutritionStats,
            'weightStats' => $weightStats,
            'currentMonth' => $month,
            'dietPeriods' => $dietPeriods,
        ]);
    }

    private function getNutritionStats($user, $startOfMonth, $endOfMonth): array
    {
        // Get excluded food dates for this user and month
        $excludedFoodDates = DailyDataExclusion::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('exclude_food', true)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $foods = $user->foods()
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->get();

        $caloriesByDay = $user->foods()
            ->select(DB::raw('DATE(consumed_at) as date'), DB::raw('SUM(total_calories) as calories'))
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->reject(function ($dayData) use ($excludedFoodDates) {
                return in_array($dayData->date, $excludedFoodDates);
            })
            ->values();

        // Calculate days with actual food data (excluding excluded days)
        $daysWithFoodData = $foods->groupBy(function ($food) {
            return Carbon::parse($food->consumed_at)->toDateString();
        })->keys()->reject(function ($date) use ($excludedFoodDates) {
            return in_array($date, $excludedFoodDates);
        })->count();

        $includedFoods = $foods->reject(function ($food) use ($excludedFoodDates) {
            $foodDate = Carbon::parse($food->consumed_at)->toDateString();

            return in_array($foodDate, $excludedFoodDates);
        });

        $averageCalories = $daysWithFoodData > 0 ? round($includedFoods->sum('total_calories') / $daysWithFoodData, 2) : 0;

        return [
            'totalCalories' => $includedFoods->sum('total_calories'),
            'totalProtein' => $includedFoods->sum('total_protein'),
            'totalCarbs' => $includedFoods->sum('total_carbs'),
            'totalFat' => $includedFoods->sum('total_fat'),
            'caloriesByDay' => $caloriesByDay,
            'averageDailyCalories' => $averageCalories,
            'excludedFoodDates' => $excludedFoodDates,
        ];
    }

    private function getWeightStats($user, $startOfMonth, $endOfMonth): array
    {
        // Get excluded weight dates for this user and month
        $excludedWeightDates = DailyDataExclusion::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('exclude_weight', true)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $weightData = $user->dailyWeights()
            ->select('date', 'weight')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('date')
            ->get()
            ->reject(function ($weight) use ($excludedWeightDates) {
                return in_array($weight->date->toDateString(), $excludedWeightDates);
            })
            ->map(function ($weight) {
                return [
                    'date' => $weight->date->toDateString(),
                    'weight' => (float) $weight->weight,
                ];
            })
            ->values();

        return [
            'weightByDay' => $weightData,
            'excludedWeightDates' => $excludedWeightDates,
        ];
    }

    private function getDietPeriods($user, $startOfMonth, $endOfMonth): array
    {
        // Get periods that overlap with the current month
        $periods = DietPeriod::where('user_id', $user->id)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where(function ($q) use ($startOfMonth, $endOfMonth) {
                    // Period starts before or during the month and ends during or after the month
                    $q->where('start_date', '<=', $endOfMonth)
                        ->where(function ($subQuery) use ($startOfMonth) {
                            $subQuery->whereNull('end_date')
                                ->orWhere('end_date', '>=', $startOfMonth);
                        });
                });
            })
            ->orderBy('start_date')
            ->get();

        // Format periods for chart consumption
        return $periods->map(function ($period) use ($startOfMonth, $endOfMonth) {
            $periodStart = $period->start_date->max($startOfMonth);
            $periodEnd = $period->end_date ? $period->end_date->min($endOfMonth) : $endOfMonth;

            return [
                'id' => $period->id,
                'name' => $period->name,
                'phase_type' => $period->phase_type,
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'target_calories' => $period->target_calories,
                'target_protein' => $period->target_protein,
            ];
        })->values()->toArray();
    }
}
