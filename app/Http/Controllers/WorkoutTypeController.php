<?php

namespace App\Http\Controllers;

use App\Models\WorkoutType;
use Illuminate\Http\Request;

class WorkoutTypeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:workout_types,name',
            'muscle_group' => 'required|string|max:255',
            'equipment_needed' => 'nullable|string|max:255',
            'sides' => 'required|in:both,left_right',
            'description' => 'nullable|string|max:1000',
        ]);

        WorkoutType::create($validated);

        return back()->with('success', 'Workout type created successfully');
    }
}
