<?php

namespace App\Http\Controllers;

use App\Models\DailyDataExclusion;
use App\Models\DailyWeight;
use App\Models\DietPeriod;
use App\Models\Food;
use App\Models\FoodType;
use App\Models\Workout;
use App\Models\WorkoutType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyDataController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', now()->toDateString());
        $date = Carbon::parse($selectedDate);

        // Get the active diet period for this date
        $activePeriod = DietPeriod::getActivePeriodForDate(auth()->id(), $date);

        // Get foods for this date
        $foods = Food::with('foodType')
            ->where('user_id', auth()->id())
            ->whereDate('consumed_at', $selectedDate)
            ->orderBy('consumed_at')
            ->get();

        // Calculate daily food totals
        $dailyTotals = [
            'calories' => $foods->sum('total_calories'),
            'protein' => $foods->sum('total_protein'),
            'carbs' => $foods->sum('total_carbs'),
            'fat' => $foods->sum('total_fat'),
        ];

        // Get workouts for this date
        $workouts = Workout::with('workoutType')
            ->where('user_id', auth()->id())
            ->whereDate('workout_date', $selectedDate)
            ->orderBy('workout_date')
            ->get();

        // Get available food types for quick add
        // Exclude one-time items that have already been used
        $foodTypes = FoodType::where(function ($query) {
            $query->where('is_one_time_item', false)
                ->orWhere(function ($subQuery) {
                    $subQuery->where('is_one_time_item', true)
                        ->whereDoesntHave('foods');
                });
        })->orderBy('name')->get();

        // Get available workout types
        $workoutTypes = WorkoutType::orderBy('name')->get();

        // Get daily weight for this date
        $dailyWeight = DailyWeight::getForUserAndDate(auth()->id(), $date);

        // Get daily data exclusions for this date
        $dailyExclusions = DailyDataExclusion::getForUserAndDate(auth()->id(), $date);

        return Inertia::render('DailyData/Index', [
            'selectedDate' => $selectedDate,
            'formattedDate' => $date->format('l, F j, Y'),
            'activePeriod' => $activePeriod,
            'foods' => $foods,
            'dailyTotals' => $dailyTotals,
            'workouts' => $workouts,
            'foodTypes' => $foodTypes,
            'workoutTypes' => $workoutTypes,
            'dailyWeight' => $dailyWeight,
            'dailyExclusions' => $dailyExclusions,
        ]);
    }

    public function storeFood(Request $request)
    {
        $validated = $request->validate([
            'food_type_id' => 'required|exists:food_types,id',
            'servings' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'consumed_at' => 'required|date',
        ]);

        $foodType = FoodType::findOrFail($validated['food_type_id']);

        // Calculate totals based on servings
        $quantityGrams = $validated['servings'] * $foodType->serving_size_grams;

        Food::create([
            'user_id' => auth()->id(),
            'food_type_id' => $validated['food_type_id'],
            'servings' => $validated['servings'],
            'quantity_grams' => $quantityGrams,
            'total_calories' => $validated['servings'] * $foodType->calories_per_serving,
            'total_protein' => $validated['servings'] * $foodType->protein_per_serving,
            'total_carbs' => $validated['servings'] * $foodType->carbs_per_serving,
            'total_fat' => $validated['servings'] * $foodType->fat_per_serving,
            'consumed_at' => $validated['consumed_at'],
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Food logged successfully');
    }

    public function storeWorkout(Request $request)
    {
        $validated = $request->validate([
            'workout_type_id' => 'required|exists:workout_types,id',
            'duration_minutes' => 'required|integer|min:1',
            'intensity' => 'required|in:low,moderate,high',
            'notes' => 'nullable|string',
            'workout_date' => 'required|date',
        ]);

        $workoutType = WorkoutType::findOrFail($validated['workout_type_id']);

        // Calculate calories burned based on duration and intensity multiplier
        $intensityMultipliers = ['low' => 0.8, 'moderate' => 1.0, 'high' => 1.2];
        $multiplier = $intensityMultipliers[$validated['intensity']];
        $caloriesBurned = ($validated['duration_minutes'] * $workoutType->calories_per_minute) * $multiplier;

        Workout::create([
            'user_id' => auth()->id(),
            'workout_type_id' => $validated['workout_type_id'],
            'duration_minutes' => $validated['duration_minutes'],
            'calories_burned' => $caloriesBurned,
            'workout_date' => $validated['workout_date'],
            'intensity' => $validated['intensity'],
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Workout logged successfully');
    }

    public function resetDay(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $date = $validated['date'];
        $userId = auth()->id();

        // Delete all foods for this date
        Food::where('user_id', $userId)
            ->whereDate('consumed_at', $date)
            ->delete();

        // Delete all workouts for this date
        Workout::where('user_id', $userId)
            ->whereDate('workout_date', $date)
            ->delete();

        // Delete daily weight for this date
        DailyWeight::where('user_id', $userId)
            ->where('date', $date)
            ->delete();

        return back()->with('success', 'All data for this day has been reset');
    }

    public function storeWeight(Request $request)
    {
        $validated = $request->validate([
            'weight' => 'required|numeric|min:0|max:9999',
            'notes' => 'nullable|string|max:1000',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date']);

        DailyWeight::upsertForUserAndDate(
            auth()->id(),
            $date,
            $validated['weight'],
            $validated['notes']
        );

        return back()->with('success', 'Weight logged successfully');
    }

    public function toggleDayExclusion(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'data_type' => 'required|in:food,workout,weight',
        ]);

        $date = Carbon::parse($validated['date']);

        DailyDataExclusion::toggleExclusion(
            auth()->id(),
            $date,
            $validated['data_type']
        );

        $isExcluded = DailyDataExclusion::isExcluded(
            auth()->id(),
            $date,
            $validated['data_type']
        );

        $status = $isExcluded ? 'excluded from' : 'included in';
        $dataTypeName = ucfirst($validated['data_type']);

        return back()->with('success', "{$dataTypeName} data for {$date->format('M j')} {$status} dataset");
    }
}
