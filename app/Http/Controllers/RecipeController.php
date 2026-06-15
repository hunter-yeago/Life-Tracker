<?php

namespace App\Http\Controllers;

use App\Models\FoodType;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $recipes = Recipe::query()
            ->with(['foodType', 'sources'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Recipes/Create', [
            'foodTypes' => FoodType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRecipe($request);

        $sources = $validated['sources'] ?? [];
        unset($validated['sources']);

        $recipe = Recipe::create($validated);

        foreach ($sources as $source) {
            $recipe->sources()->create($source);
        }

        return redirect()->route('recipes.index')
            ->with('success', 'Recipe created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Recipe $recipe): Response
    {
        $recipe->load(['foodType', 'sources']);

        return Inertia::render('Recipes/Show', [
            'recipe' => $recipe,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recipe $recipe): Response
    {
        $recipe->load('sources');

        return Inertia::render('Recipes/Edit', [
            'recipe' => $recipe,
            'foodTypes' => FoodType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $validated = $this->validateRecipe($request);

        $sources = $validated['sources'] ?? [];
        unset($validated['sources']);

        $recipe->update($validated);

        $recipe->sources()->delete();
        foreach ($sources as $source) {
            $recipe->sources()->create($source);
        }

        return redirect()->route('recipes.index')
            ->with('success', 'Recipe updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return redirect()->route('recipes.index')
            ->with('success', 'Recipe deleted successfully!');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRecipe(Request $request): array
    {
        return $request->validate([
            'food_type_id' => 'nullable|exists:food_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'instructions' => 'nullable|string',
            'servings' => 'nullable|string|max:255',
            'sources' => 'nullable|array',
            'sources.*.title' => 'required|string|max:255',
            'sources.*.url' => 'nullable|string|max:2048',
            'sources.*.type' => 'nullable|string|max:255',
        ]);
    }
}
