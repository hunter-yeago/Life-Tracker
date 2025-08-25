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
        $dietPeriodId = request('diet_period_id');
        
        // If no specific view is requested, default to diet period view
        if (!$dietPeriodId && !request('month')) {
            $latestDietPeriod = DietPeriod::where('user_id', $user->id)
                ->orderBy('start_date', 'desc')
                ->first();
            if ($latestDietPeriod) {
                $dietPeriodId = $latestDietPeriod->id;
            }
        }
        
        // Determine date range - either by month or by diet period
        if ($dietPeriodId) {
            $dietPeriod = DietPeriod::where('user_id', $user->id)->findOrFail($dietPeriodId);
            $startDate = $dietPeriod->start_date;
            $endDate = $dietPeriod->end_date ?: Carbon::now()->endOfDay();
        } else {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        }

        $nutritionStats = $this->getNutritionStats($user, $startDate, $endDate);
        $weightStats = $this->getWeightStats($user, $startDate, $endDate);
        $dietPeriods = $this->getAllDietPeriods($user);

        return Inertia::render('Dashboard', [
            'nutritionStats' => $nutritionStats,
            'weightStats' => $weightStats,
            'currentMonth' => $month,
            'dietPeriods' => $dietPeriods,
            'selectedDietPeriodId' => $dietPeriodId ? (int) $dietPeriodId : null,
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    private function getNutritionStats($user, $startOfMonth, $endOfMonth): array
    {
        // Get excluded food dates for this user and month
        $excludedFoodData = DailyDataExclusion::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('exclude_food', true)
            ->get(['date', 'food_notes'])
            ->mapWithKeys(function ($exclusion) {
                return [Carbon::parse($exclusion->date)->toDateString() => $exclusion->food_notes];
            })
            ->toArray();
        
        $excludedFoodDates = array_keys($excludedFoodData);

        $foods = $user->foods()
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->get();

        $caloriesByDay = $user->foods()
            ->select(
                DB::raw('DATE(consumed_at) as date'), 
                DB::raw('SUM(total_calories) as calories'),
                DB::raw("GROUP_CONCAT(CASE WHEN notes IS NOT NULL AND notes != '' THEN notes ELSE NULL END) as notes")
            )
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->reject(function ($dayData) use ($excludedFoodDates) {
                return in_array($dayData->date, $excludedFoodDates);
            })
            ->map(function ($dayData) {
                return [
                    'date' => $dayData->date,
                    'calories' => $dayData->calories,
                    'notes' => $dayData->notes ? explode(',', $dayData->notes) : []
                ];
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
            'excludedFoodData' => $excludedFoodData,
        ];
    }

    private function getWeightStats($user, $startOfMonth, $endOfMonth): array
    {
        // Get excluded weight dates for this user and month
        $excludedWeightData = DailyDataExclusion::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('exclude_weight', true)
            ->get(['date', 'weight_notes'])
            ->mapWithKeys(function ($exclusion) {
                return [Carbon::parse($exclusion->date)->toDateString() => $exclusion->weight_notes];
            })
            ->toArray();
        
        $excludedWeightDates = array_keys($excludedWeightData);

        $weightData = $user->dailyWeights()
            ->select('date', 'weight', 'notes')
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
                    'notes' => $weight->notes,
                ];
            })
            ->values();

        return [
            'weightByDay' => $weightData,
            'excludedWeightDates' => $excludedWeightDates,
            'excludedWeightData' => $excludedWeightData,
        ];
    }

    private function getAllDietPeriods($user): array
    {
        // Get all diet periods for the user for the dropdown
        $periods = DietPeriod::where('user_id', $user->id)
            ->orderBy('start_date', 'desc')
            ->get();

        return $periods->map(function ($period) {
            return [
                'id' => $period->id,
                'name' => $period->name,
                'phase_type' => $period->phase_type,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
                'target_calories' => $period->target_calories,
                'target_protein' => $period->target_protein,
            ];
        })->values()->toArray();
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
