<?php

namespace App\Http\Controllers;

use App\Models\DailyWeight;
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
        $weightData = $user->dailyWeights()
            ->select('date', 'weight')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(function ($weight) {
                return [
                    'date' => $weight->date->toDateString(),
                    'weight' => (float) $weight->weight,
                ];
            });

        return [
            'weightByDay' => $weightData,
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
