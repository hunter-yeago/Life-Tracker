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
            ->get();

        $oneTimeFoodTypes = FoodType::oneTimeItems()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('FoodTypes/Index', [
            'regularFoodTypes' => [
                'data' => $regularFoodTypes,
                'links' => [],
                'meta' => []
            ],
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
        $isOneTimeItem = $request->boolean('is_one_time_item');

        // For one-time items, don't validate uniqueness and modify the name immediately
        if ($isOneTimeItem) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'calories_per_serving' => 'required|numeric|min:0',
                'protein_per_serving' => 'required|numeric|min:0',
                'carbs_per_serving' => 'required|numeric|min:0',
                'fat_per_serving' => 'required|numeric|min:0',
                'is_one_time_item' => 'boolean',
            ]);

            // Always make the name unique by appending timestamp
            $originalName = $validated['name'];
            $timestampSuffix = ' ('.now()->format('M j, g:i A').')';
            $uniqueName = $originalName.$timestampSuffix;

            // Ensure the generated name is also unique (in case of rapid submissions)
            $counter = 1;
            while (FoodType::where('name', $uniqueName)->exists()) {
                $uniqueName = $originalName.$timestampSuffix.' #'.$counter;
                $counter++;
            }

            $validated['name'] = $uniqueName;
        } else {
            // For regular items, validate uniqueness
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:food_types,name',
                'description' => 'nullable|string',
                'calories_per_serving' => 'required|numeric|min:0',
                'protein_per_serving' => 'required|numeric|min:0',
                'carbs_per_serving' => 'required|numeric|min:0',
                'fat_per_serving' => 'required|numeric|min:0',
                'is_one_time_item' => 'boolean',
            ]);
        }

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

    public function usage(FoodType $foodType)
    {
        $usageData = $foodType->foods()
            ->selectRaw('DATE(consumed_at) as usage_date')
            ->distinct()
            ->orderBy('usage_date', 'desc')
            ->pluck('usage_date')
            ->groupBy(function ($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m');
            })
            ->map(function ($dates) {
                return $dates->map(function ($date) {
                    return \Carbon\Carbon::parse($date)->format('j');
                })->sort()->values();
            });

        return response()->json($usageData);
    }

    public function macroData(FoodType $foodType)
    {
        $macroData = $foodType->foods()
            ->selectRaw('DATE(consumed_at) as date, SUM(total_protein) as protein, SUM(total_carbs) as carbs, SUM(total_fat) as fat')
            ->where('consumed_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'protein' => round($item->protein, 1),
                    'carbs' => round($item->carbs, 1),
                    'fat' => round($item->fat, 1)
                ];
            });

        return response()->json($macroData);
    }
}
