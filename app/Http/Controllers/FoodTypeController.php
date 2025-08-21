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
        
        $foodTypes = FoodType::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('FoodTypes/Index', [
            'foodTypes' => $foodTypes,
            'search' => $search,
        ]);
    }

    public function show(FoodType $foodType): Response
    {
        return Inertia::render('FoodTypes/Show', [
            'foodType' => $foodType,
        ]);
    }

    public function update(Request $request, FoodType $foodType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'calories_per_100g' => 'required|numeric|min:0',
            'protein_per_100g' => 'required|numeric|min:0',
            'carbs_per_100g' => 'required|numeric|min:0',
            'fat_per_100g' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
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