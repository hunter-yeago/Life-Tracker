<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutRequest;
use App\Models\Workout;
use App\Models\WorkoutType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    public function index(): Response
    {
        $workouts = auth()->user()->workouts()
            ->with('workoutType')
            ->latest('performed_at')
            ->paginate(15);

        return Inertia::render('Workouts/Index', [
            'workouts' => $workouts,
        ]);
    }

    public function create(): Response
    {
        $workoutTypes = WorkoutType::all();

        return Inertia::render('Workouts/Create', [
            'workoutTypes' => $workoutTypes,
        ]);
    }

    public function store(StoreWorkoutRequest $request): RedirectResponse
    {
        auth()->user()->workouts()->create($request->validated());

        return redirect()->route('workouts.index')
            ->with('success', 'Workout logged successfully!');
    }

    public function show(Workout $workout): Response
    {
        $this->authorize('view', $workout);

        $workout->load('workoutType');

        return Inertia::render('Workouts/Show', [
            'workout' => $workout,
        ]);
    }

    public function edit(Workout $workout): Response
    {
        $this->authorize('update', $workout);

        $workoutTypes = WorkoutType::all();

        return Inertia::render('Workouts/Edit', [
            'workout' => $workout,
            'workoutTypes' => $workoutTypes,
        ]);
    }

    public function update(StoreWorkoutRequest $request, Workout $workout): RedirectResponse
    {
        $this->authorize('update', $workout);

        $workout->update($request->validated());

        return redirect()->route('workouts.index')
            ->with('success', 'Workout updated successfully!');
    }

    public function destroy(Workout $workout): RedirectResponse
    {
        $this->authorize('delete', $workout);

        $workout->delete();

        return redirect()->route('workouts.index')
            ->with('success', 'Workout deleted successfully!');
    }

    public function toggleExclusion(Workout $workout)
    {
        // Ensure the user owns this workout entry
        if ($workout->user_id !== auth()->id()) {
            abort(403);
        }

        $workout->update([
            'exclude_from_dataset' => ! $workout->exclude_from_dataset,
        ]);

        $status = $workout->exclude_from_dataset ? 'excluded from' : 'included in';

        return back()->with('success', "Workout data {$status} dataset");
    }
}
