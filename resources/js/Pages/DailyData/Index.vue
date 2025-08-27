<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, reactive } from 'vue';
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

interface DailyWeight {
    id: number;
    weight: number;
    notes?: string;
    date: string;
}

interface DailyDataExclusion {
    id?: number;
    exclude_food: boolean;
    exclude_workout: boolean;
    exclude_weight: boolean;
    food_notes?: string;
    workout_notes?: string;
    weight_notes?: string;
    date: string;
}

interface DailyNote {
    id?: number;
    note?: string;
    date: string;
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
    dailyWeight?: DailyWeight;
    dailyExclusions?: DailyDataExclusion;
    dailyNote?: DailyNote;
}

const props = defineProps<Props>();

const showFoodForm = ref(false);
const showWorkoutForm = ref(false);
const showWeightForm = ref(false);
const editingFoodId = ref<number | null>(null);
const editForm = ref({
    servings: '',
    notes: ''
});
const showCreateFoodType = ref(false);

const foodForm = useForm({
    food_type_id: '',
    servings: '1',
    notes: '',
});

const createFoodTypeForm = useForm({
    name: '',
    calories_per_serving: '',
    protein_per_serving: '',
    carbs_per_serving: '',
    fat_per_serving: '',
    description: '',
    is_one_time_item: false,
});

const workoutForm = useForm({
    workout_type_id: '',
    duration_minutes: '30',
    intensity: 'moderate',
    notes: '',
    workout_date: props.selectedDate + 'T12:00',
});

const weightForm = useForm({
    weight: props.dailyWeight?.weight?.toString() || '',
    notes: props.dailyWeight?.notes || '',
    date: props.selectedDate,
});

const dailyNoteForm = useForm({
    note: props.dailyNote?.note || '',
    date: props.selectedDate,
});

// Update the form when the daily weight changes (e.g., when navigating dates)
watch(() => props.dailyWeight, (newWeight) => {
    weightForm.weight = newWeight?.weight?.toString() || '';
    weightForm.notes = newWeight?.notes || '';
    weightForm.date = props.selectedDate;
}, { deep: true });

// Update the form when the daily note changes (e.g., when navigating dates)
watch(() => props.dailyNote, (newNote) => {
    dailyNoteForm.note = newNote?.note || '';
    dailyNoteForm.date = props.selectedDate;
}, { deep: true });

// Also update when the selected date changes
watch(() => props.selectedDate, (newDate) => {
    weightForm.date = newDate;
    dailyNoteForm.date = newDate;
});

const dateInput = ref<HTMLInputElement>();

// Exclusion form states
const showExclusionForms = ref({
    food: false,
    workout: false,
    weight: false,
});

const exclusionForms = reactive({
    food: {
        excluded: props.dailyExclusions?.exclude_food || false,
        note: props.dailyExclusions?.food_notes || '',
    },
    workout: {
        excluded: props.dailyExclusions?.exclude_workout || false,
        note: props.dailyExclusions?.workout_notes || '',
    },
    weight: {
        excluded: props.dailyExclusions?.exclude_weight || false,
        note: props.dailyExclusions?.weight_notes || '',
    },
});

// Update exclusion forms when daily exclusions change
watch(() => props.dailyExclusions, (newExclusions) => {
    if (newExclusions) {
        exclusionForms.food.excluded = newExclusions.exclude_food || false;
        exclusionForms.food.note = newExclusions.food_notes || '';
        exclusionForms.workout.excluded = newExclusions.exclude_workout || false;
        exclusionForms.workout.note = newExclusions.workout_notes || '';
        exclusionForms.weight.excluded = newExclusions.exclude_weight || false;
        exclusionForms.weight.note = newExclusions.weight_notes || '';
    }
}, { deep: true });

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
    // Don't submit if we're in create mode
    if (showCreateFoodType.value || foodForm.food_type_id === 'create-new') {
        return;
    }
    
    // Set the date in the form data
    foodForm.transform((data) => ({
        ...data,
        date: props.selectedDate
    }));
    
    foodForm.post(route('daily-data.food'), {
        onSuccess: () => {
            // Keep the form open and reset only the fields for next entry
            foodForm.reset('food_type_id', 'servings', 'notes');
            foodForm.servings = '1';
            
            // Focus back on food type selector for quick next entry
            setTimeout(() => {
                const foodTypeSelect = document.querySelector('#food_type_id') as HTMLSelectElement;
                if (foodTypeSelect) {
                    foodTypeSelect.focus();
                }
            }, 100);
        },
        preserveScroll: true
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

function submitWeight() {
    weightForm.post(route('daily-data.weight'), {
        onSuccess: () => {
            showWeightForm.value = false;
        }
    });
}

function submitDailyNote() {
    dailyNoteForm.post(route('daily-data.note'), {
        onSuccess: () => {
            // Note saved successfully
        },
        preserveScroll: true
    });
}

function toggleDayExclusion(dataType: 'food' | 'workout' | 'weight') {
    router.patch(route('daily-data.toggle-day-exclusion'), {
        date: props.selectedDate,
        data_type: dataType,
    }, {
        preserveScroll: true,
    });
}

function toggleExclusionForm(dataType: 'food' | 'workout' | 'weight') {
    showExclusionForms.value[dataType] = !showExclusionForms.value[dataType];
}

function saveExclusion(dataType: 'food' | 'workout' | 'weight') {
    const formData = exclusionForms[dataType];
    
    router.patch(route('daily-data.toggle-day-exclusion'), {
        date: props.selectedDate,
        data_type: dataType,
        excluded: formData.excluded,
        note: formData.note,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showExclusionForms.value[dataType] = false;
        }
    });
}

function cancelExclusion(dataType: 'food' | 'workout' | 'weight') {
    // Reset form to current state
    const exclusions = props.dailyExclusions;
    if (exclusions) {
        // Reset excluded state
        if (dataType === 'food') {
            exclusionForms.food.excluded = exclusions.exclude_food || false;
            exclusionForms.food.note = exclusions.food_notes || '';
        } else if (dataType === 'workout') {
            exclusionForms.workout.excluded = exclusions.exclude_workout || false;
            exclusionForms.workout.note = exclusions.workout_notes || '';
        } else if (dataType === 'weight') {
            exclusionForms.weight.excluded = exclusions.exclude_weight || false;
            exclusionForms.weight.note = exclusions.weight_notes || '';
        }
    }
    showExclusionForms.value[dataType] = false;
}

function resetDay() {
    if (confirm('Are you sure you want to reset ALL data for this day? This will delete all food and workout entries and cannot be undone.')) {
        router.delete(route('daily-data.reset'), {
            data: { date: props.selectedDate },
            preserveScroll: true,
        });
    }
}

function startEditingFood(food: Food) {
    editingFoodId.value = food.id;
    editForm.value = {
        servings: food.servings.toString(),
        notes: food.notes || ''
    };
}

function cancelEditingFood() {
    editingFoodId.value = null;
    editForm.value = {
        servings: '',
        notes: ''
    };
}

function saveEditFood(food: Food) {
    const servings = parseFloat(editForm.value.servings);
    if (!servings || servings <= 0) {
        alert('Please enter a valid serving amount');
        return;
    }

    router.put(route('foods.update', food.id), {
        servings: servings,
        notes: editForm.value.notes
    }, {
        onSuccess: () => {
            editingFoodId.value = null;
            router.reload({ 
                only: ['foods', 'dailyTotals'],
                preserveUrl: true 
            });
        },
        onError: (errors) => {
            console.error('Update failed:', errors);
        }
    });
}

function deleteFood(foodId: number) {
    if (confirm('Are you sure you want to delete this food entry?')) {
        router.delete(route('foods.destroy', foodId), {
            onSuccess: () => {
                router.reload({ 
                    only: ['foods', 'dailyTotals'],
                    preserveUrl: true 
                });
            }
        });
    }
}

function getEditedNutrition(food: Food) {
    const servings = parseFloat(editForm.value.servings) || 0;
    return {
        calories: Math.round(food.food_type.calories_per_serving * servings),
        protein: Math.round(food.food_type.protein_per_serving * servings * 10) / 10,
        carbs: Math.round(food.food_type.carbs_per_serving * servings * 10) / 10,
        fat: Math.round(food.food_type.fat_per_serving * servings * 10) / 10,
    };
}

const selectedFoodType = computed(() => {
    if (!foodForm.food_type_id || foodForm.food_type_id === 'create-new') return null;
    return props.foodTypes.find(ft => ft.id == Number(foodForm.food_type_id));
});

const estimatedFoodCalories = computed(() => {
    if (!selectedFoodType.value) return 0;
    return Math.round(selectedFoodType.value.calories_per_serving * Number(foodForm.servings));
});

function updateSelectedFoodType() {
    if (foodForm.food_type_id === 'create-new') {
        showCreateFoodType.value = true;
    } else {
        showCreateFoodType.value = false;
    }
}

function submitCreateFoodType() {
    createFoodTypeForm.post(route('food-types.store'), {
        onSuccess: (page) => {
            createFoodTypeForm.reset();
            showCreateFoodType.value = false;
            // Reload the page to get the updated food types
            router.reload({
                only: ['foodTypes'],
                preserveScroll: true,
                onSuccess: () => {
                    // Auto-select the newly created food type
                    if (page.props && page.props.newlyCreatedFoodType) {
                        foodForm.food_type_id = page.props.newlyCreatedFoodType.id.toString();
                    }
                }
            });
        },
        onError: (errors) => {
            console.error('Food type creation failed:', errors);
        }
    });
}


const selectedWorkoutType = computed(() => {
    if (!workoutForm.workout_type_id) return null;
    return props.workoutTypes.find(wt => wt.id == Number(workoutForm.workout_type_id));
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
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ formattedDate }}
                    </h3>
                    <button
                        @click="resetDay"
                        v-if="foods.length > 0 || workouts.length > 0"
                        class="inline-flex items-center px-3 py-1 border border-red-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:bg-red-900/20 dark:text-red-400 dark:border-red-700 dark:hover:bg-red-900/30"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Reset Day
                    </button>
                </div>

                <!-- Daily Note -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3">
                    <form @submit.prevent="submitDailyNote" class="flex items-start gap-3">
                        <div class="flex-1">
                            <textarea
                                v-model="dailyNoteForm.note"
                                placeholder="Daily note..."
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                                rows="2"
                            ></textarea>
                            <InputError class="mt-1" :message="dailyNoteForm.errors.note" />
                        </div>
                        <PrimaryButton type="submit" :disabled="dailyNoteForm.processing" class="text-sm px-3 py-2">
                            Save
                        </PrimaryButton>
                    </form>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Weight</div>
                        <div class="text-2xl font-bold text-purple-600">
                            {{ dailyWeight ? `${Math.round(dailyWeight.weight * 10) / 10} lbs` : 'Not set' }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Calories Consumed</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ Math.round(dailyTotals.calories) }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Protein</div>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ Math.round(dailyTotals.protein) }}g
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Carbs</div>
                        <div class="text-2xl font-bold text-orange-600">
                            {{ Math.round(dailyTotals.carbs) }}g
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Fat</div>
                        <div class="text-2xl font-bold text-purple-600">
                            {{ Math.round(dailyTotals.fat) }}g
                        </div>
                    </div>
                </div>

                <!-- Day-Level Exclusions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Data Exclusions</h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Exclude entire day's data from calculations and charts. Excluded data affects averages and statistical analysis.
                    </div>
                    <div class="space-y-4">
                        <!-- Food Data Exclusion -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Food Data</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ dailyExclusions?.exclude_food ? 'Excluded from dataset' : 'Included in dataset' }}
                                        {{ dailyExclusions?.food_notes ? ' • Has note' : '' }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="toggleExclusionForm('food')"
                                        class="px-3 py-1 text-sm font-medium rounded-md bg-blue-600 hover:bg-blue-700 text-white transition-colors"
                                    >
                                        {{ showExclusionForms.food ? 'Cancel' : 'Edit' }}
                                    </button>
                                    <button
                                        @click="toggleDayExclusion('food')"
                                        :class="dailyExclusions?.exclude_food 
                                            ? 'bg-green-600 hover:bg-green-700 text-white' 
                                            : 'bg-red-600 hover:bg-red-700 text-white'"
                                        class="px-3 py-1 text-sm font-medium rounded-md transition-colors"
                                    >
                                        {{ dailyExclusions?.exclude_food ? 'Include' : 'Exclude' }}
                                    </button>
                                </div>
                            </div>
                            <div v-if="showExclusionForms.food" class="p-4 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800">
                                <div class="space-y-4">
                                    <div>
                                        <label class="flex items-center">
                                            <input 
                                                v-model="exclusionForms.food.excluded"
                                                type="checkbox" 
                                                class="rounded border-gray-300 text-red-600 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600"
                                            >
                                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Exclude food data from calculations
                                            </span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Exclusion Note (optional)
                                        </label>
                                        <textarea 
                                            v-model="exclusionForms.food.note"
                                            rows="2" 
                                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500" 
                                            placeholder="Why is this day excluded? (e.g., incomplete data, special circumstances)"
                                        ></textarea>
                                    </div>
                                    <div class="flex gap-2">
                                        <button 
                                            @click="saveExclusion('food')"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                                        >
                                            Save
                                        </button>
                                        <button 
                                            @click="cancelExclusion('food')"
                                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Workout Data Exclusion -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Workout Data</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ dailyExclusions?.exclude_workout ? 'Excluded from dataset' : 'Included in dataset' }}
                                        {{ dailyExclusions?.workout_notes ? ' • Has note' : '' }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="toggleExclusionForm('workout')"
                                        class="px-3 py-1 text-sm font-medium rounded-md bg-blue-600 hover:bg-blue-700 text-white transition-colors"
                                    >
                                        {{ showExclusionForms.workout ? 'Cancel' : 'Edit' }}
                                    </button>
                                    <button
                                        @click="toggleDayExclusion('workout')"
                                        :class="dailyExclusions?.exclude_workout 
                                            ? 'bg-green-600 hover:bg-green-700 text-white' 
                                            : 'bg-red-600 hover:bg-red-700 text-white'"
                                        class="px-3 py-1 text-sm font-medium rounded-md transition-colors"
                                    >
                                        {{ dailyExclusions?.exclude_workout ? 'Include' : 'Exclude' }}
                                    </button>
                                </div>
                            </div>
                            <div v-if="showExclusionForms.workout" class="p-4 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800">
                                <div class="space-y-4">
                                    <div>
                                        <label class="flex items-center">
                                            <input 
                                                v-model="exclusionForms.workout.excluded"
                                                type="checkbox" 
                                                class="rounded border-gray-300 text-red-600 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600"
                                            >
                                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Exclude workout data from calculations
                                            </span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Exclusion Note (optional)
                                        </label>
                                        <textarea 
                                            v-model="exclusionForms.workout.note"
                                            rows="2" 
                                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500" 
                                            placeholder="Why is this day excluded? (e.g., incomplete data, special circumstances)"
                                        ></textarea>
                                    </div>
                                    <div class="flex gap-2">
                                        <button 
                                            @click="saveExclusion('workout')"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                                        >
                                            Save
                                        </button>
                                        <button 
                                            @click="cancelExclusion('workout')"
                                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Weight Data Exclusion -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Weight Data</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ dailyExclusions?.exclude_weight ? 'Excluded from dataset' : 'Included in dataset' }}
                                        {{ dailyExclusions?.weight_notes ? ' • Has note' : '' }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="toggleExclusionForm('weight')"
                                        class="px-3 py-1 text-sm font-medium rounded-md bg-blue-600 hover:bg-blue-700 text-white transition-colors"
                                    >
                                        {{ showExclusionForms.weight ? 'Cancel' : 'Edit' }}
                                    </button>
                                    <button
                                        @click="toggleDayExclusion('weight')"
                                        :class="dailyExclusions?.exclude_weight 
                                            ? 'bg-green-600 hover:bg-green-700 text-white' 
                                            : 'bg-red-600 hover:bg-red-700 text-white'"
                                        class="px-3 py-1 text-sm font-medium rounded-md transition-colors"
                                    >
                                        {{ dailyExclusions?.exclude_weight ? 'Include' : 'Exclude' }}
                                    </button>
                                </div>
                            </div>
                            <div v-if="showExclusionForms.weight" class="p-4 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800">
                                <div class="space-y-4">
                                    <div>
                                        <label class="flex items-center">
                                            <input 
                                                v-model="exclusionForms.weight.excluded"
                                                type="checkbox" 
                                                class="rounded border-gray-300 text-red-600 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600"
                                            >
                                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Exclude weight data from calculations
                                            </span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Exclusion Note (optional)
                                        </label>
                                        <textarea 
                                            v-model="exclusionForms.weight.note"
                                            rows="2" 
                                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500" 
                                            placeholder="Why is this day excluded? (e.g., incomplete data, special circumstances)"
                                        ></textarea>
                                    </div>
                                    <div class="flex gap-2">
                                        <button 
                                            @click="saveExclusion('weight')"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                                        >
                                            Save
                                        </button>
                                        <button 
                                            @click="cancelExclusion('weight')"
                                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weight Section -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Weight</h4>
                        <PrimaryButton @click="showWeightForm = !showWeightForm">
                            {{ dailyWeight ? 'Update Weight' : 'Log Weight' }}
                        </PrimaryButton>
                    </div>

                    <!-- Weight Form -->
                    <div v-if="showWeightForm" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <form @submit.prevent="submitWeight" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="weight" value="Weight (lbs)" />
                                    <TextInput
                                        id="weight"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="9999"
                                        v-model="weightForm.weight"
                                        class="w-full"
                                        required
                                        placeholder="e.g., 185.5"
                                    />
                                    <InputError :message="weightForm.errors.weight" />
                                </div>
                                <div>
                                    <InputLabel for="weight_notes" value="Notes" />
                                    <TextInput
                                        id="weight_notes"
                                        v-model="weightForm.notes"
                                        class="w-full"
                                        placeholder="Optional notes..."
                                    />
                                    <InputError :message="weightForm.errors.notes" />
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <PrimaryButton type="submit" :disabled="weightForm.processing">
                                    {{ dailyWeight ? 'Update Weight' : 'Log Weight' }}
                                </PrimaryButton>
                                <SecondaryButton @click="showWeightForm = false">
                                    Cancel
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Current Weight Display -->
                    <div v-if="dailyWeight && !showWeightForm" class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-lg font-semibold text-purple-900 dark:text-purple-100">
                                    {{ Math.round(dailyWeight.weight * 10) / 10 }} lbs
                                </div>
                                <div v-if="dailyWeight.notes" class="text-sm text-purple-700 dark:text-purple-300 italic">
                                    {{ dailyWeight.notes }}
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    @click="showWeightForm = true"
                                    class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200 text-sm font-medium"
                                >
                                    Edit
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="!dailyWeight && !showWeightForm" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No weight logged for this day
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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="food_type_id" value="Food Type" />
                                    <select
                                        id="food_type_id"
                                        v-model="foodForm.food_type_id"
                                        @change="updateSelectedFoodType"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        :required="!showCreateFoodType"
                                    >
                                        <option value="">Select food type...</option>
                                        <option value="create-new">➕ Create New Food</option>
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
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Form will stay open for multiple entries
                                </div>
                                <div class="flex space-x-2">
                                    <PrimaryButton type="submit" :disabled="foodForm.processing">
                                        Add Food
                                    </PrimaryButton>
                                    <SecondaryButton @click="showFoodForm = false">
                                        Done
                                    </SecondaryButton>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Create New Food Type Form -->
                    <div v-if="showCreateFoodType" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-700 rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h5 class="text-lg font-semibold text-green-800 dark:text-green-200">Create New Food Type</h5>
                            <button
                                @click="showCreateFoodType = false; foodForm.food_type_id = ''"
                                class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 text-sm"
                            >
                                Cancel
                            </button>
                        </div>

                        <form @submit.prevent="submitCreateFoodType" class="space-y-4">
                            <div>
                                <InputLabel for="name" value="Food Name *" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="w-full"
                                    v-model="createFoodTypeForm.name"
                                    placeholder="e.g., Refried Beans"
                                    required
                                />
                                <InputError class="mt-2" :message="createFoodTypeForm.errors.name" />
                            </div>

                            <div>
                                <h6 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    Nutrition Facts Per Serving
                                </h6>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <InputLabel for="calories_per_serving" value="Calories *" />
                                        <TextInput
                                            id="calories_per_serving"
                                            type="number"
                                            step="0.1"
                                            class="w-full"
                                            v-model="createFoodTypeForm.calories_per_serving"
                                            placeholder="140"
                                            required
                                        />
                                        <InputError class="mt-2" :message="createFoodTypeForm.errors.calories_per_serving" />
                                    </div>
                                    <div>
                                        <InputLabel for="protein_per_serving" value="Protein (g) *" />
                                        <TextInput
                                            id="protein_per_serving"
                                            type="number"
                                            step="0.1"
                                            class="w-full"
                                            v-model="createFoodTypeForm.protein_per_serving"
                                            placeholder="8"
                                            required
                                        />
                                        <InputError class="mt-2" :message="createFoodTypeForm.errors.protein_per_serving" />
                                    </div>
                                    <div>
                                        <InputLabel for="carbs_per_serving" value="Carbs (g) *" />
                                        <TextInput
                                            id="carbs_per_serving"
                                            type="number"
                                            step="0.1"
                                            class="w-full"
                                            v-model="createFoodTypeForm.carbs_per_serving"
                                            placeholder="19"
                                            required
                                        />
                                        <InputError class="mt-2" :message="createFoodTypeForm.errors.carbs_per_serving" />
                                    </div>
                                    <div>
                                        <InputLabel for="fat_per_serving" value="Fat (g) *" />
                                        <TextInput
                                            id="fat_per_serving"
                                            type="number"
                                            step="0.1"
                                            class="w-full"
                                            v-model="createFoodTypeForm.fat_per_serving"
                                            placeholder="4.5"
                                            required
                                        />
                                        <InputError class="mt-2" :message="createFoodTypeForm.errors.fat_per_serving" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input
                                    id="is_one_time_item"
                                    type="checkbox"
                                    v-model="createFoodTypeForm.is_one_time_item"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <label for="is_one_time_item" class="ml-2 text-sm text-gray-900 dark:text-gray-300">
                                    One-time item (won't appear in dropdown for future meals)
                                </label>
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton :disabled="createFoodTypeForm.processing">
                                    Create Food Type
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Food List -->
                    <div v-if="foods.length > 0" class="space-y-2">
                        <div v-for="food in foods" :key="food.id" class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                            <!-- Regular Display Mode -->
                            <div v-if="editingFoodId !== food.id">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ food.food_type.name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ food.servings }} serving(s) • {{ Math.round(food.total_calories) }} cal
                                        </div>
                                        <div v-if="food.notes" class="text-xs text-gray-500 dark:text-gray-400 italic mt-1">
                                            {{ food.notes }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex space-x-3">
                                    <button
                                        @click="startEditingFood(food)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteFood(food.id)"
                                        class="text-red-600 hover:text-red-800 text-sm font-medium"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Edit Mode -->
                            <div v-else class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ food.food_type.name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ getEditedNutrition(food).calories }} cal • 
                                            P: {{ getEditedNutrition(food).protein }}g • 
                                            C: {{ getEditedNutrition(food).carbs }}g • 
                                            F: {{ getEditedNutrition(food).fat }}g
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Servings
                                        </label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            v-model="editForm.servings"
                                            class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            placeholder="e.g., 1.5"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Notes
                                        </label>
                                        <input
                                            type="text"
                                            v-model="editForm.notes"
                                            class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            placeholder="Optional notes..."
                                        />
                                    </div>
                                </div>
                                
                                <div class="flex space-x-3 justify-end">
                                    <button
                                        @click="cancelEditingFood"
                                        class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 text-sm font-medium px-3 py-1"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="saveEditFood(food)"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-3 py-1 rounded"
                                    >
                                        Save
                                    </button>
                                </div>
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
                        <div v-for="workout in workouts" :key="workout.id" class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ workout.workout_type.name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ workout.duration_minutes }}min • {{ workout.intensity }} intensity
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
                    </div>
                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No workout entries for this day
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>