<?php

namespace App\Http\Controllers;

use App\Models\DietPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DietPeriodController extends Controller
{
    public function index()
    {
        $periods = DietPeriod::where('user_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->get();

        return Inertia::render('DietPeriods/Index', [
            'periods' => $periods,
            'currentPeriod' => DietPeriod::getCurrentPeriod(auth()->id()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phase_type' => 'required|in:cut,bulk,maintenance',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'target_calories' => 'nullable|numeric|min:0',
            'target_protein' => 'nullable|numeric|min:0',
            'target_carbs' => 'nullable|numeric|min:0',
            'target_fat' => 'nullable|numeric|min:0',
            'starting_weight' => 'nullable|numeric|min:0',
            'target_weight' => 'nullable|numeric|min:0',
        ]);

        // End any ongoing periods if this is starting today or in the past
        $startDate = Carbon::parse($validated['start_date']);
        if ($startDate->lessThanOrEqualTo(now())) {
            DietPeriod::where('user_id', auth()->id())
                ->whereNull('end_date')
                ->where('start_date', '<', $startDate)
                ->update(['end_date' => $startDate->subDay()]);
        }

        DietPeriod::create([
            'user_id' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', 'Diet period created successfully');
    }

    public function update(Request $request, DietPeriod $dietPeriod)
    {
        if ($dietPeriod->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phase_type' => 'required|in:cut,bulk,maintenance',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'target_calories' => 'nullable|numeric|min:0',
            'target_protein' => 'nullable|numeric|min:0',
            'target_carbs' => 'nullable|numeric|min:0',
            'target_fat' => 'nullable|numeric|min:0',
            'starting_weight' => 'nullable|numeric|min:0',
            'target_weight' => 'nullable|numeric|min:0',
        ]);

        $dietPeriod->update($validated);

        return back()->with('success', 'Diet period updated successfully');
    }

    public function destroy(DietPeriod $dietPeriod)
    {
        if ($dietPeriod->user_id !== auth()->id()) {
            abort(403);
        }

        $dietPeriod->delete();

        return back()->with('success', 'Diet period deleted successfully');
    }

    public function endCurrent(Request $request)
    {
        $validated = $request->validate([
            'end_date' => 'required|date|before_or_equal:today',
        ]);

        $currentPeriod = DietPeriod::getCurrentPeriod(auth()->id());

        if (! $currentPeriod) {
            return back()->with('error', 'No active period to end');
        }

        $currentPeriod->update([
            'end_date' => $validated['end_date'],
        ]);

        return back()->with('success', 'Current period ended successfully');
    }
}
