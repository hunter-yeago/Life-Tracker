<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutTypeRequest;
use App\Models\WorkoutType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutTypeController extends Controller
{
    public function index(): Response
    {
        $workoutTypes = WorkoutType::withCount('workouts')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('WorkoutTypes/Index', [
            'workoutTypes' => $workoutTypes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('WorkoutTypes/Create');
    }

    public function store(StoreWorkoutTypeRequest $request): RedirectResponse
    {
        WorkoutType::create($request->validated());

        return redirect()->route('workout-types.index')
            ->with('success', 'Workout type created successfully!');
    }

    public function show(WorkoutType $workoutType): Response
    {
        $workoutType->load('workouts.user');

        return Inertia::render('WorkoutTypes/Show', [
            'workoutType' => $workoutType,
        ]);
    }

    public function edit(WorkoutType $workoutType): Response
    {
        return Inertia::render('WorkoutTypes/Edit', [
            'workoutType' => $workoutType,
        ]);
    }

    public function update(StoreWorkoutTypeRequest $request, WorkoutType $workoutType): RedirectResponse
    {
        \Log::info('Update request', [
            'validated' => $request->validated(),
            'workoutType' => $workoutType->toArray(),
            'route_param' => $request->route('workoutType'),
        ]);

        $workoutType->update($request->validated());

        return redirect()->route('workout-types.index')
            ->with('success', 'Workout type updated successfully!');
    }

    public function destroy(WorkoutType $workoutType): RedirectResponse
    {
        if ($workoutType->workouts()->count() > 0) {
            return redirect()->route('workout-types.index')
                ->with('error', 'Cannot delete workout type that has associated workouts.');
        }

        $workoutType->delete();

        return redirect()->route('workout-types.index')
            ->with('success', 'Workout type deleted successfully!');
    }
}
