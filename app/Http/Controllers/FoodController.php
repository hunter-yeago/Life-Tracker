<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodRequest;
use App\Models\Food;
use App\Models\FoodType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'label' => $monthDate->format('F Y')
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

    public function create(): Response
    {
        $foodTypes = FoodType::all();

        return Inertia::render('Foods/Create', [
            'foodTypes' => $foodTypes,
        ]);
    }

    public function store(StoreFoodRequest $request): RedirectResponse
    {
        $foodType = FoodType::findOrFail($request->food_type_id);
        $quantityGrams = $request->quantity_grams;

        $totalCalories = ($foodType->calories_per_100g * $quantityGrams) / 100;
        $totalProtein = ($foodType->protein_per_100g * $quantityGrams) / 100;
        $totalCarbs = ($foodType->carbs_per_100g * $quantityGrams) / 100;
        $totalFat = ($foodType->fat_per_100g * $quantityGrams) / 100;

        auth()->user()->foods()->create([
            'food_type_id' => $request->food_type_id,
            'quantity_grams' => $quantityGrams,
            'total_calories' => $totalCalories,
            'total_protein' => $totalProtein,
            'total_carbs' => $totalCarbs,
            'total_fat' => $totalFat,
            'notes' => $request->notes,
            'consumed_at' => $request->consumed_at,
        ]);

        return redirect()->route('foods.index')
            ->with('success', 'Food intake logged successfully!');
    }

    public function show(Food $food): Response
    {
        $this->authorize('view', $food);

        $food->load('foodType');

        return Inertia::render('Foods/Show', [
            'food' => $food,
        ]);
    }

    public function edit(Food $food): Response
    {
        $this->authorize('update', $food);

        $foodTypes = FoodType::all();

        return Inertia::render('Foods/Edit', [
            'food' => $food,
            'foodTypes' => $foodTypes,
        ]);
    }

    public function update(StoreFoodRequest $request, Food $food): RedirectResponse
    {
        $this->authorize('update', $food);

        $foodType = FoodType::findOrFail($request->food_type_id);
        $quantityGrams = $request->quantity_grams;

        $totalCalories = ($foodType->calories_per_100g * $quantityGrams) / 100;
        $totalProtein = ($foodType->protein_per_100g * $quantityGrams) / 100;
        $totalCarbs = ($foodType->carbs_per_100g * $quantityGrams) / 100;
        $totalFat = ($foodType->fat_per_100g * $quantityGrams) / 100;

        $food->update([
            'food_type_id' => $request->food_type_id,
            'quantity_grams' => $quantityGrams,
            'total_calories' => $totalCalories,
            'total_protein' => $totalProtein,
            'total_carbs' => $totalCarbs,
            'total_fat' => $totalFat,
            'notes' => $request->notes,
            'consumed_at' => $request->consumed_at,
        ]);

        return redirect()->route('foods.index')
            ->with('success', 'Food intake updated successfully!');
    }

    public function destroy(Food $food): RedirectResponse
    {
        $this->authorize('delete', $food);

        $food->delete();

        return redirect()->route('foods.index')
            ->with('success', 'Food intake deleted successfully!');
    }
}