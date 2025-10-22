<?php

namespace App\Http\Controllers;

use App\Models\DailyDataExclusion;
use App\Models\DailyNote;
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
        $workouts = Workout::with(['workoutType', 'sets' => function ($query) {
            $query->orderBy('set_number');
        }])
            ->where('user_id', auth()->id())
            ->whereDate('performed_at', $selectedDate)
            ->orderBy('performed_at')
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

        // Get daily note for this date
        $dailyNote = DailyNote::getForUserAndDate(auth()->id(), $date);

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
            'dailyNote' => $dailyNote,
        ]);
    }

    public function storeFood(Request $request)
    {
        $validated = $request->validate([
            'food_type_id' => 'required|exists:food_types,id',
            'servings' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $foodType = FoodType::findOrFail($validated['food_type_id']);

        // Calculate totals based on servings
        $quantityGrams = $validated['servings'] * $foodType->serving_size_grams;

        // Use the selected date from the daily data page
        $selectedDate = $request->get('date', now()->toDateString());
        $consumedAt = Carbon::parse($selectedDate)->setTime(12, 0, 0);

        Food::create([
            'user_id' => auth()->id(),
            'food_type_id' => $validated['food_type_id'],
            'servings' => $validated['servings'],
            'quantity_grams' => $quantityGrams,
            'total_calories' => $validated['servings'] * $foodType->calories_per_serving,
            'total_protein' => $validated['servings'] * $foodType->protein_per_serving,
            'total_carbs' => $validated['servings'] * $foodType->carbs_per_serving,
            'total_fat' => $validated['servings'] * $foodType->fat_per_serving,
            'consumed_at' => $consumedAt,
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Food logged successfully');
    }

    public function storeWorkout(Request $request)
    {
        $validated = $request->validate([
            'workout_type_id' => 'required|exists:workout_types,id',
            'notes' => 'nullable|string',
            'date' => 'required|date',
            'sets' => 'required|array|min:1',
            'sets.*.set_number' => 'required|integer|min:1',
            'sets.*.reps' => 'nullable|integer|min:1',
            'sets.*.weight' => 'nullable|numeric|min:0',
            'sets.*.duration_seconds' => 'nullable|integer|min:1',
            'sets.*.difficulty' => 'nullable|in:easy,hard,really_hard,almost_fail,fail',
            'sets.*.completed' => 'boolean',
            'sets.*.notes' => 'nullable|string|max:500',
        ]);

        // Use the selected date to set performed_at for proper date filtering
        $performedAt = Carbon::parse($validated['date'])->setTime(12, 0, 0);

        // Check if this workout already exists for this user, workout type, and date
        $existingWorkout = auth()->user()->workouts()
            ->where('workout_type_id', $validated['workout_type_id'])
            ->whereDate('performed_at', $validated['date'])
            ->first();

        if ($existingWorkout) {
            // Add sets to existing workout
            $workout = $existingWorkout;

            // Get the next set number
            $nextSetNumber = $workout->sets()->max('set_number') + 1;

            foreach ($validated['sets'] as $setData) {
                $setData['set_number'] = $nextSetNumber++;
                $workout->sets()->create($setData);
            }

            $message = 'Sets added to existing workout successfully!';
        } else {
            // Create new workout
            $workoutData = [
                'workout_type_id' => $validated['workout_type_id'],
                'notes' => $validated['notes'],
                'performed_at' => $performedAt,
            ];

            $workout = auth()->user()->workouts()->create($workoutData);

            // Create the sets with proper numbering
            $setNumber = 1;
            foreach ($validated['sets'] as $setData) {
                $setData['set_number'] = $setNumber++;
                $workout->sets()->create($setData);
            }

            $message = 'Workout logged successfully!';
        }

        return back()->with('success', $message);
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
            ->whereDate('performed_at', $date)
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
            'excluded' => 'nullable|boolean',
            'note' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($validated['date']);

        // If excluded and note are provided, set the exclusion with note
        if (isset($validated['excluded'])) {
            DailyDataExclusion::setExclusionWithNote(
                auth()->id(),
                $date,
                $validated['data_type'],
                $validated['excluded'],
                $validated['note'] ?? null
            );
            $isExcluded = $validated['excluded'];
        } else {
            // Otherwise, toggle the current state (for backwards compatibility)
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
        }

        $status = $isExcluded ? 'excluded from' : 'included in';
        $dataTypeName = ucfirst($validated['data_type']);

        return back()->with('success', "{$dataTypeName} data for {$date->format('M j')} {$status} dataset");
    }

    public function storeDailyNote(Request $request)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date']);

        DailyNote::upsertForUserAndDate(
            auth()->id(),
            $date,
            $validated['note']
        );

        return back()->with('success', 'Daily note saved successfully');
    }
}
