<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodRequest;
use App\Models\Food;
use App\Models\FoodType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoodController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedDate = $request->get('date', Carbon::now()->format('Y-m-d'));
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));

        // Parse the selected date
        $date = Carbon::createFromFormat('Y-m-d', $selectedDate);

        // Get food entries for the selected day
        $foods = auth()->user()->foods()
            ->with('foodType')
            ->whereDate('consumed_at', $date)
            ->orderBy('consumed_at', 'asc')
            ->get();

        // Get daily totals
        $dailyTotals = auth()->user()->foods()
            ->whereDate('consumed_at', $date)
            ->selectRaw('
                SUM(total_calories) as calories,
                SUM(total_protein) as protein,
                SUM(total_carbs) as carbs,
                SUM(total_fat) as fat
            ')
            ->first();

        // Get available dates for the selected month
        $startOfMonth = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $endOfMonth = Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth();

        $availableDates = auth()->user()->foods()
            ->whereBetween('consumed_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(consumed_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date')
            ->toArray();

        // Generate month options (last 12 months)
        $monthOptions = collect();
        for ($i = 0; $i < 12; $i++) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthOptions->push([
                'value' => $monthDate->format('Y-m'),
                'label' => $monthDate->format('F Y'),
            ]);
        }

        return Inertia::render('Foods/Index', [
            'foods' => $foods,
            'dailyTotals' => $dailyTotals,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'availableDates' => $availableDates,
            'monthOptions' => $monthOptions,
        ]);
    }

    public function create(Request $request): Response
    {
        $selectedDate = $request->get('date', Carbon::now()->format('Y-m-d'));

        // Parse the selected date
        $date = Carbon::createFromFormat('Y-m-d', $selectedDate);

        $foodTypes = FoodType::regularItems()->get();

        // Get food entries for the selected day
        $foods = auth()->user()->foods()
            ->with('foodType')
            ->whereDate('consumed_at', $date)
            ->orderBy('consumed_at', 'asc')
            ->get();

        // Get daily totals
        $dailyTotals = auth()->user()->foods()
            ->whereDate('consumed_at', $date)
            ->selectRaw('
                SUM(total_calories) as calories,
                SUM(total_protein) as protein,
                SUM(total_carbs) as carbs,
                SUM(total_fat) as fat
            ')
            ->first();

        return Inertia::render('Foods/Create', [
            'foodTypes' => $foodTypes,
            'selectedDate' => $selectedDate,
            'foods' => $foods,
            'dailyTotals' => $dailyTotals,
        ]);
    }

    public function store(StoreFoodRequest $request): RedirectResponse
    {
        $foodType = FoodType::findOrFail($request->food_type_id);
        $servings = $request->servings;

        // Calculate nutrition based on servings
        $totalCalories = $foodType->calories_per_serving * $servings;
        $totalProtein = $foodType->protein_per_serving * $servings;
        $totalCarbs = $foodType->carbs_per_serving * $servings;
        $totalFat = $foodType->fat_per_serving * $servings;

        auth()->user()->foods()->create([
            'food_type_id' => $request->food_type_id,
            'servings' => $servings,
            'quantity_grams' => null,
            'total_calories' => $totalCalories,
            'total_protein' => $totalProtein,
            'total_carbs' => $totalCarbs,
            'total_fat' => $totalFat,
            'notes' => $request->notes,
            'consumed_at' => $request->consumed_at,
        ]);

        $consumedDate = Carbon::parse($request->consumed_at)->format('Y-m-d');

        return redirect()->route('foods.create', ['date' => $consumedDate])
            ->with('success', 'Food intake logged successfully!');
    }

    public function show(Food $food): Response
    {
        // Only show foods belonging to the authenticated user
        if ($food->user_id !== auth()->id()) {
            abort(403);
        }

        $food->load('foodType');

        return Inertia::render('Foods/Show', [
            'food' => $food,
        ]);
    }

    public function edit(Food $food): Response
    {
        // Only edit foods belonging to the authenticated user
        if ($food->user_id !== auth()->id()) {
            abort(403);
        }

        $food->load('foodType');
        
        return Inertia::render('Foods/Edit', [
            'food' => $food,
        ]);
    }

    public function update(Request $request, Food $food): RedirectResponse
    {
        // Only update foods belonging to the authenticated user
        if ($food->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'servings' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $servings = $request->servings;
        $foodType = $food->foodType;

        // Calculate nutrition based on new servings
        $totalCalories = $foodType->calories_per_serving * $servings;
        $totalProtein = $foodType->protein_per_serving * $servings;
        $totalCarbs = $foodType->carbs_per_serving * $servings;
        $totalFat = $foodType->fat_per_serving * $servings;

        $food->update([
            'servings' => $servings,
            'total_calories' => $totalCalories,
            'total_protein' => $totalProtein,
            'total_carbs' => $totalCarbs,
            'total_fat' => $totalFat,
            'notes' => $request->notes,
        ]);

        $consumedDate = Carbon::parse($food->consumed_at)->format('Y-m-d');

        return redirect()->route('foods.create', ['date' => $consumedDate])
            ->with('success', 'Food entry updated successfully!');
    }

    public function destroy(Food $food): RedirectResponse
    {
        // Only delete foods belonging to the authenticated user
        if ($food->user_id !== auth()->id()) {
            abort(403);
        }

        $consumedDate = Carbon::parse($food->consumed_at)->format('Y-m-d');
        $food->delete();

        return redirect()->route('foods.create', ['date' => $consumedDate])
            ->with('success', 'Food entry deleted successfully!');
    }
}
