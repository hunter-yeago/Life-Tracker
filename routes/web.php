<?php

use App\Http\Controllers\DailyDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DietPeriodController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FoodTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Daily Data Entry (New Central System)
    Route::get('/daily-data', [DailyDataController::class, 'index'])->name('daily-data.index');
    Route::post('/daily-data/food', [DailyDataController::class, 'storeFood'])->name('daily-data.food');
    Route::post('/daily-data/workout', [DailyDataController::class, 'storeWorkout'])->name('daily-data.workout');
    Route::post('/daily-data/weight', [DailyDataController::class, 'storeWeight'])->name('daily-data.weight');
    Route::delete('/daily-data/reset', [DailyDataController::class, 'resetDay'])->name('daily-data.reset');

    // Diet Periods (Historical Periods System)
    Route::get('/diet-periods', [DietPeriodController::class, 'index'])->name('diet-periods.index');
    Route::post('/diet-periods', [DietPeriodController::class, 'store'])->name('diet-periods.store');
    Route::put('/diet-periods/{dietPeriod}', [DietPeriodController::class, 'update'])->name('diet-periods.update');
    Route::delete('/diet-periods/{dietPeriod}', [DietPeriodController::class, 'destroy'])->name('diet-periods.destroy');
    Route::post('/diet-periods/end-current', [DietPeriodController::class, 'endCurrent'])->name('diet-periods.end-current');

    Route::resource('workouts', WorkoutController::class);
    Route::resource('foods', FoodController::class);
    Route::get('api/food-types/{food_type}/usage', [FoodTypeController::class, 'usage'])->name('food-types.usage');
    Route::resource('food-types', FoodTypeController::class)->except(['create']);
});

require __DIR__.'/auth.php';
