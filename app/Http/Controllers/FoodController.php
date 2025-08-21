<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodRequest;
use App\Models\Food;
use App\Models\FoodType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FoodController extends Controller
{
    public function index(): Response
    {
        $foods = auth()->user()->foods()
            ->with('foodType')
            ->latest('consumed_at')
            ->paginate(15);

        return Inertia::render('Foods/Index', [
            'foods' => $foods,
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
