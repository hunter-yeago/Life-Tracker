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
            ->with(['workoutType', 'sets'])
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
            'preselected' => [
                'workout_type_id' => request('workout_type_id'),
                'performed_at' => request('performed_at'),
            ],
        ]);
    }

    public function store(StoreWorkoutRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        // Check if this workout already exists for this user, workout type, and date
        $existingWorkout = auth()->user()->workouts()
            ->where('workout_type_id', $validatedData['workout_type_id'])
            ->whereDate('performed_at', $validatedData['performed_at'])
            ->first();

        if ($existingWorkout) {
            // Add sets to existing workout
            $workout = $existingWorkout;

            // Get the next set number
            $nextSetNumber = $workout->sets()->max('set_number') + 1;

            foreach ($validatedData['sets'] as $setData) {
                $setData['set_number'] = $nextSetNumber++;
                $workout->sets()->create($setData);
            }

            $message = 'Sets added to existing workout successfully!';
        } else {
            // Create new workout
            $workoutData = [
                'workout_type_id' => $validatedData['workout_type_id'],
                'notes' => $validatedData['notes'],
                'performed_at' => $validatedData['performed_at'],
            ];

            $workout = auth()->user()->workouts()->create($workoutData);

            // Create the sets with proper numbering
            $setNumber = 1;
            foreach ($validatedData['sets'] as $setData) {
                $setData['set_number'] = $setNumber++;
                $workout->sets()->create($setData);
            }

            $message = 'Workout logged successfully!';
        }

        return redirect()->route('workouts.show', $workout)
            ->with('success', $message);
    }

    public function show(Workout $workout): Response
    {
        $this->authorize('view', $workout);

        $workout->load(['workoutType', 'sets' => function ($query) {
            $query->orderBy('set_number');
        }]);

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
