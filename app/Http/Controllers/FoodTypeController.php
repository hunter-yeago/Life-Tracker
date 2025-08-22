<?php

namespace App\Http\Controllers;

use App\Models\FoodType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoodTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');

        $regularFoodTypes = FoodType::regularItems()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $oneTimeFoodTypes = FoodType::oneTimeItems()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('FoodTypes/Index', [
            'regularFoodTypes' => $regularFoodTypes,
            'oneTimeFoodTypes' => $oneTimeFoodTypes,
            'search' => $search,
        ]);
    }

    public function show(FoodType $foodType): Response
    {
        return Inertia::render('FoodTypes/Show', [
            'foodType' => $foodType,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'calories_per_serving' => 'required|numeric|min:0',
            'protein_per_serving' => 'required|numeric|min:0',
            'carbs_per_serving' => 'required|numeric|min:0',
            'fat_per_serving' => 'required|numeric|min:0',
            'is_one_time_item' => 'boolean',
        ]);

        // Set defaults for fields not collected in the form but needed in the database
        $validated['serving_size'] = '1 serving';
        $validated['category'] = 'Food';

        $foodType = FoodType::create($validated);

        return redirect()->back()
            ->with('success', 'Food type created successfully!')
            ->with('newly_created_food_type_id', $foodType->id);
    }

    public function update(Request $request, FoodType $foodType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'serving_size' => 'nullable|string|max:255',
            'serving_weight_grams' => 'nullable|numeric|min:0',
            'calories_per_serving' => 'required|numeric|min:0',
            'protein_per_serving' => 'required|numeric|min:0',
            'carbs_per_serving' => 'required|numeric|min:0',
            'fat_per_serving' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'is_one_time_item' => 'boolean',
        ]);

        $foodType->update($validated);

        return redirect()->route('food-types.index')
            ->with('success', 'Food type updated successfully!');
    }

    public function destroy(FoodType $foodType)
    {
        $foodType->delete();

        return redirect()->route('food-types.index')
            ->with('success', 'Food type deleted successfully!');
    }
}
