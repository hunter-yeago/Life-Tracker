<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

interface FoodType {
    id: number;
    name: string;
    category: string;
    calories_per_serving: number;
    protein_per_serving: number;
    carbs_per_serving: number;
    fat_per_serving: number;
    serving_size_grams: number;
}

interface WorkoutType {
    id: number;
    name: string;
    category: string;
    calories_per_minute: number;
}

interface Food {
    id: number;
    food_type: FoodType;
    servings: number;
    total_calories: number;
    total_protein: number;
    total_carbs: number;
    total_fat: number;
    consumed_at: string;
    notes?: string;
}

interface Workout {
    id: number;
    workout_type: WorkoutType;
    duration_minutes: number;
    calories_burned: number;
    intensity: string;
    workout_date: string;
    notes?: string;
}

interface DietPeriod {
    id: number;
    name: string;
    phase_type: string;
    start_date: string;
    end_date?: string;
    notes?: string;
    target_calories?: number;
    target_protein?: number;
    target_carbs?: number;
    target_fat?: number;
    starting_weight?: number;
    target_weight?: number;
}

interface DailyTotals {
    calories: number;
    protein: number;
    carbs: number;
    fat: number;
}

interface Props {
    selectedDate: string;
    formattedDate: string;
    activePeriod?: DietPeriod;
    foods: Food[];
    dailyTotals: DailyTotals;
    workouts: Workout[];
    foodTypes: FoodType[];
    workoutTypes: WorkoutType[];
}

const props = defineProps<Props>();

const showFoodForm = ref(false);
const showWorkoutForm = ref(false);

const foodForm = useForm({
    food_type_id: '',
    servings: '1',
    notes: '',
    consumed_at: props.selectedDate + 'T12:00',
});

const workoutForm = useForm({
    workout_type_id: '',
    duration_minutes: '30',
    intensity: 'moderate',
    notes: '',
    workout_date: props.selectedDate + 'T12:00',
});

const dateInput = ref<HTMLInputElement>();

function changeDate(event: Event) {
    const target = event.target as HTMLInputElement;
    router.get(route('daily-data.index'), { date: target.value }, {
        preserveScroll: true,
        onSuccess: () => {
            // Restore focus to the date input after navigation
            setTimeout(() => {
                if (dateInput.value) {
                    dateInput.value.focus();
                }
            }, 50);
        }
    });
}

function handleDateKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
        event.preventDefault();
        const currentDate = new Date(props.selectedDate);
        const newDate = new Date(currentDate);
        
        if (event.key === 'ArrowUp') {
            newDate.setDate(currentDate.getDate() + 1);
        } else {
            newDate.setDate(currentDate.getDate() - 1);
        }
        
        const formattedDate = newDate.toISOString().split('T')[0];
        router.get(route('daily-data.index'), { date: formattedDate }, {
            preserveScroll: true,
            onSuccess: () => {
                setTimeout(() => {
                    if (dateInput.value) {
                        dateInput.value.focus();
                    }
                }, 50);
            }
        });
    }
}


function submitFood() {
    foodForm.post(route('daily-data.food'), {
        onSuccess: () => {
            showFoodForm.value = false;
            foodForm.reset('food_type_id', 'servings', 'notes');
            foodForm.servings = '1';
            foodForm.consumed_at = props.selectedDate + 'T12:00';
        }
    });
}

function submitWorkout() {
    workoutForm.post(route('daily-data.workout'), {
        onSuccess: () => {
            showWorkoutForm.value = false;
            workoutForm.reset('workout_type_id', 'duration_minutes', 'notes');
            workoutForm.duration_minutes = '30';
            workoutForm.intensity = 'moderate';
            workoutForm.workout_date = props.selectedDate + 'T12:00';
        }
    });
}

const selectedFoodType = computed(() => {
    if (!foodForm.food_type_id) return null;
    return props.foodTypes.find(ft => ft.id == Number(foodForm.food_type_id));
});

const estimatedFoodCalories = computed(() => {
    if (!selectedFoodType.value) return 0;
    return Math.round(selectedFoodType.value.calories_per_serving * Number(foodForm.servings));
});

const selectedWorkoutType = computed(() => {
    if (!workoutForm.workout_type_id) return null;
    return props.workoutTypes.find(wt => wt.id == Number(workoutForm.workout_type_id));
});

const estimatedCaloriesBurned = computed(() => {
    if (!selectedWorkoutType.value) return 0;
    const intensityMultipliers = { low: 0.8, moderate: 1.0, high: 1.2 };
    const multiplier = intensityMultipliers[workoutForm.intensity as keyof typeof intensityMultipliers];
    return Math.round(selectedWorkoutType.value.calories_per_minute * Number(workoutForm.duration_minutes) * multiplier);
});

const netCalories = computed(() => {
    const caloriesConsumed = props.dailyTotals.calories;
    const caloriesBurned = props.workouts.reduce((sum, w) => sum + w.calories_burned, 0);
    return Math.round(caloriesConsumed - caloriesBurned);
});

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function getPhaseColor(phase: string) {
    const colors = {
        cut: 'text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400',
        bulk: 'text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400',
        maintenance: 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400'
    };
    return colors[phase as keyof typeof colors] || colors.maintenance;
}
</script>

<template>
    <Head title="Daily Data Entry" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Daily Data Entry
                </h2>
                <input
                    ref="dateInput"
                    type="date"
                    :value="selectedDate"
                    @change="changeDate"
                    @keydown="handleDateKeydown"
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                />
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Date Header -->
                <div class="text-center">
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ formattedDate }}
                    </h3>
                </div>

                <!-- Active Diet Period Section -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Current Diet Period</h4>
                        <Link
                            :href="route('diet-periods.index')"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        >
                            Manage Periods
                        </Link>
                    </div>

                    <div v-if="activePeriod" class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ activePeriod.name }}
                            </h5>
                            <span :class="`px-2 py-1 text-xs font-medium rounded-full capitalize ${getPhaseColor(activePeriod.phase_type)}`">
                                {{ activePeriod.phase_type }}
                            </span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Active
                            </span>
                        </div>
                        
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Started {{ formatDate(activePeriod.start_date) }}
                            <span v-if="activePeriod.end_date">
                                - ends {{ formatDate(activePeriod.end_date) }}
                            </span>
                        </div>

                        <div v-if="activePeriod.notes" class="text-sm text-gray-600 dark:text-gray-300 italic">
                            {{ activePeriod.notes }}
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-if="activePeriod.target_calories" class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-center">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Target Calories</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ Math.round(activePeriod.target_calories) }}
                                </div>
                            </div>
                            <div v-if="activePeriod.target_protein" class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-center">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Target Protein</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ Math.round(activePeriod.target_protein) }}g
                                </div>
                            </div>
                            <div v-if="activePeriod.starting_weight" class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-center">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Starting Weight</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ activePeriod.starting_weight }} lbs
                                </div>
                            </div>
                            <div v-if="activePeriod.target_weight" class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg text-center">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Target Weight</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ activePeriod.target_weight }} lbs
                                </div>
                            </div>
                        </div>

                        <!-- Target Progress Bar -->
                        <div v-if="activePeriod.target_calories" class="mt-4">
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                <span>Calories: {{ Math.round(dailyTotals.calories) }} / {{ Math.round(activePeriod.target_calories) }}</span>
                                <span>{{ Math.round((dailyTotals.calories / activePeriod.target_calories) * 100) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div 
                                    class="bg-blue-600 h-2 rounded-full" 
                                    :style="`width: ${Math.min(100, (dailyTotals.calories / activePeriod.target_calories) * 100)}%`"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <div class="text-gray-500 dark:text-gray-400 mb-4">
                            No active diet period for this date
                        </div>
                        <Link
                            :href="route('diet-periods.index')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Create Diet Period
                        </Link>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Calories Consumed</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ Math.round(dailyTotals.calories) }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Calories Burned</div>
                        <div class="text-2xl font-bold text-red-600">
                            {{ Math.round(workouts.reduce((sum, w) => sum + w.calories_burned, 0)) }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Net Calories</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ netCalories }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Protein</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ Math.round(dailyTotals.protein) }}g
                        </div>
                    </div>
                </div>

                <!-- Food Section -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Food Entries</h4>
                        <PrimaryButton @click="showFoodForm = !showFoodForm">
                            Log Food
                        </PrimaryButton>
                    </div>

                    <!-- Food Form -->
                    <div v-if="showFoodForm" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <form @submit.prevent="submitFood" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <InputLabel for="food_type_id" value="Food Type" />
                                    <select
                                        id="food_type_id"
                                        v-model="foodForm.food_type_id"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        required
                                    >
                                        <option value="">Select food type...</option>
                                        <optgroup v-for="category in [...new Set(foodTypes.map(ft => ft.category))]" :key="category" :label="category">
                                            <option v-for="foodType in foodTypes.filter(ft => ft.category === category)" :key="foodType.id" :value="foodType.id">
                                                {{ foodType.name }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <InputError :message="foodForm.errors.food_type_id" />
                                </div>
                                <div>
                                    <InputLabel for="servings" value="Servings" />
                                    <TextInput
                                        id="servings"
                                        type="number"
                                        step="0.1"
                                        min="0.1"
                                        v-model="foodForm.servings"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="foodForm.errors.servings" />
                                </div>
                                <div>
                                    <InputLabel for="consumed_at" value="Time Consumed" />
                                    <TextInput
                                        id="consumed_at"
                                        type="datetime-local"
                                        v-model="foodForm.consumed_at"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="foodForm.errors.consumed_at" />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="food_notes" value="Notes" />
                                <TextInput
                                    id="food_notes"
                                    v-model="foodForm.notes"
                                    class="w-full"
                                />
                                <InputError :message="foodForm.errors.notes" />
                            </div>
                            <div v-if="selectedFoodType" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="text-sm text-blue-700 dark:text-blue-300">
                                    Estimated: {{ estimatedFoodCalories }} calories
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <PrimaryButton type="submit" :disabled="foodForm.processing">
                                    Log Food
                                </PrimaryButton>
                                <SecondaryButton @click="showFoodForm = false">
                                    Cancel
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Food List -->
                    <div v-if="foods.length > 0" class="space-y-2">
                        <div v-for="food in foods" :key="food.id" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ food.food_type.name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ food.servings }} serving(s) • {{ Math.round(food.total_calories) }} cal
                                </div>
                                <div v-if="food.notes" class="text-xs text-gray-500 dark:text-gray-400 italic">
                                    {{ food.notes }}
                                </div>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ new Date(food.consumed_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No food entries for this day
                    </div>
                </div>

                <!-- Workout Section -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Workout Entries</h4>
                        <PrimaryButton @click="showWorkoutForm = !showWorkoutForm">
                            Log Workout
                        </PrimaryButton>
                    </div>

                    <!-- Workout Form -->
                    <div v-if="showWorkoutForm" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <form @submit.prevent="submitWorkout" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <InputLabel for="workout_type_id" value="Workout Type" />
                                    <select
                                        id="workout_type_id"
                                        v-model="workoutForm.workout_type_id"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        required
                                    >
                                        <option value="">Select workout type...</option>
                                        <optgroup v-for="category in [...new Set(workoutTypes.map(wt => wt.category))]" :key="category" :label="category">
                                            <option v-for="workoutType in workoutTypes.filter(wt => wt.category === category)" :key="workoutType.id" :value="workoutType.id">
                                                {{ workoutType.name }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <InputError :message="workoutForm.errors.workout_type_id" />
                                </div>
                                <div>
                                    <InputLabel for="duration_minutes" value="Duration (minutes)" />
                                    <TextInput
                                        id="duration_minutes"
                                        type="number"
                                        min="1"
                                        v-model="workoutForm.duration_minutes"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="workoutForm.errors.duration_minutes" />
                                </div>
                                <div>
                                    <InputLabel for="intensity" value="Intensity" />
                                    <select
                                        id="intensity"
                                        v-model="workoutForm.intensity"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="low">Low</option>
                                        <option value="moderate">Moderate</option>
                                        <option value="high">High</option>
                                    </select>
                                    <InputError :message="workoutForm.errors.intensity" />
                                </div>
                            </div>
                            <div>
                                <InputLabel for="workout_date" value="Workout Time" />
                                <TextInput
                                    id="workout_date"
                                    type="datetime-local"
                                    v-model="workoutForm.workout_date"
                                    class="w-full"
                                    required
                                />
                                <InputError :message="workoutForm.errors.workout_date" />
                            </div>
                            <div>
                                <InputLabel for="workout_notes" value="Notes" />
                                <TextInput
                                    id="workout_notes"
                                    v-model="workoutForm.notes"
                                    class="w-full"
                                />
                                <InputError :message="workoutForm.errors.notes" />
                            </div>
                            <div v-if="selectedWorkoutType" class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <div class="text-sm text-red-700 dark:text-red-300">
                                    Estimated: {{ estimatedCaloriesBurned }} calories burned
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <PrimaryButton type="submit" :disabled="workoutForm.processing">
                                    Log Workout
                                </PrimaryButton>
                                <SecondaryButton @click="showWorkoutForm = false">
                                    Cancel
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Workout List -->
                    <div v-if="workouts.length > 0" class="space-y-2">
                        <div v-for="workout in workouts" :key="workout.id" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ workout.workout_type.name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ workout.duration_minutes }}min • {{ workout.intensity }} intensity • {{ Math.round(workout.calories_burned) }} cal burned
                                </div>
                                <div v-if="workout.notes" class="text-xs text-gray-500 dark:text-gray-400 italic">
                                    {{ workout.notes }}
                                </div>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ new Date(workout.workout_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No workout entries for this day
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>