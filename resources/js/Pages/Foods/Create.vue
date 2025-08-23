<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';

interface FoodType {
    id: number;
    name: string;
    description: string;
    serving_size: string;
    serving_weight_grams: number;
    calories_per_serving: number;
    protein_per_serving: number;
    carbs_per_serving: number;
    fat_per_serving: number;
    category: string;
    is_one_time_item: boolean;
}

interface Food {
    id: number;
    food_type: {
        id: number;
        name: string;
        category: string;
        serving_size?: string;
        calories_per_serving: number;
        protein_per_serving: number;
        carbs_per_serving: number;
        fat_per_serving: number;
    };
    servings: number;
    quantity_grams?: number;
    total_calories: number;
    total_protein: number;
    total_carbs: number;
    total_fat: number;
    consumed_at: string;
    notes?: string;
}

interface DailyTotals {
    calories: number;
    protein: number;
    carbs: number;
    fat: number;
}

interface Props {
    foodTypes: FoodType[];
    selectedDate: string;
    foods: Food[];
    dailyTotals: DailyTotals;
    newlyCreatedFoodTypeId?: number;
}

const props = defineProps<Props>();

const selectedDate = ref(props.selectedDate);

const form = useForm({
    food_type_id: '',
    servings: '1',
    consumed_at: selectedDate.value,
    notes: '',
});

// Watch for date changes and update form
watch(selectedDate, (newDate) => {
    form.consumed_at = newDate;
    // Fetch foods for the new date
    router.get(route('foods.create'), { date: newDate }, { preserveState: true });
});

const selectedFoodType = ref<FoodType | null>(null);
const editingFoodId = ref<number | null>(null);
const editForm = ref({
    servings: '',
    notes: ''
});
const showCreateFoodType = ref(false);
const createFoodTypeForm = useForm({
    name: '',
    calories_per_serving: '',
    protein_per_serving: '',
    carbs_per_serving: '',
    fat_per_serving: '',
    description: '',
    is_one_time_item: false,
});


const updateSelectedFoodType = () => {
    if (form.food_type_id === 'create-new') {
        showCreateFoodType.value = true;
        selectedFoodType.value = null;
    } else {
        const foodTypeId = parseInt(form.food_type_id);
        selectedFoodType.value = props.foodTypes.find(ft => ft.id === foodTypeId) || null;
        showCreateFoodType.value = false;
    }
};

const startEditing = (food: Food) => {
    editingFoodId.value = food.id;
    editForm.value = {
        servings: food.servings.toString(),
        notes: food.notes || ''
    };
};

const cancelEditing = () => {
    editingFoodId.value = null;
    editForm.value = {
        servings: '',
        notes: ''
    };
};

const saveEdit = (food: Food) => {
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
                preserveState: true,
                preserveScroll: true 
            });
        },
        onError: (errors) => {
            console.error('Update failed:', errors);
        }
    });
};

const getEditedNutrition = (food: Food) => {
    const servings = parseFloat(editForm.value.servings) || 0;
    return {
        calories: Math.round(food.food_type.calories_per_serving * servings),
        protein: Math.round(food.food_type.protein_per_serving * servings * 10) / 10,
        carbs: Math.round(food.food_type.carbs_per_serving * servings * 10) / 10,
        fat: Math.round(food.food_type.fat_per_serving * servings * 10) / 10,
    };
};

const toggleCreateFoodType = () => {
    showCreateFoodType.value = !showCreateFoodType.value;
    if (!showCreateFoodType.value) {
        createFoodTypeForm.reset();
    }
};

const submitCreateFoodType = () => {
    createFoodTypeForm.post(route('food-types.store'), {
        onSuccess: () => {
            createFoodTypeForm.reset();
            showCreateFoodType.value = false;
            // Reload page to get updated food types
            router.reload({ 
                only: ['foodTypes'],
                preserveState: true,
                preserveScroll: true 
            });
        },
        onError: (errors) => {
            console.error('Food type creation failed:', errors);
        }
    });
};

const estimatedNutrition = computed(() => {
    if (!selectedFoodType.value || !form.servings) {
        return null;
    }

    const multiplier = parseFloat(form.servings);
    const displayQuantity = `${form.servings} serving${multiplier !== 1 ? 's' : ''}`;

    return {
        displayQuantity,
        calories: Math.round(selectedFoodType.value.calories_per_serving * multiplier),
        protein: Math.round(selectedFoodType.value.protein_per_serving * multiplier * 10) / 10,
        carbs: Math.round(selectedFoodType.value.carbs_per_serving * multiplier * 10) / 10,
        fat: Math.round(selectedFoodType.value.fat_per_serving * multiplier * 10) / 10,
    };
});

const submit = () => {
    form.post(route('foods.store'), {
        onSuccess: () => {
            // Reset form but keep the date
            form.reset('food_type_id', 'servings', 'notes');
            selectedFoodType.value = null;
            // Refresh the page data without full reload
            router.reload({ 
                only: ['foods', 'dailyTotals'],
                preserveState: true,
                preserveScroll: true 
            });
        }
    });
};

const deleteFood = (foodId: number) => {
    if (confirm('Are you sure you want to delete this food entry?')) {
        router.delete(route('foods.destroy', foodId), {
            onSuccess: () => {
                router.reload({ 
                    only: ['foods', 'dailyTotals'],
                    preserveState: true,
                    preserveScroll: true 
                });
            }
        });
    }
};

const formattedDate = computed(() => {
    // Parse the date as local time to avoid timezone issues
    const [year, month, day] = selectedDate.value.split('-');
    const date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    return date.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
});


// Helper function to display servings without unnecessary zeros
const displayServings = (servings: number) => {
    return servings % 1 === 0 ? servings.toString() : servings.toString();
};

// Auto-select newly created food type
onMounted(() => {
    if (props.newlyCreatedFoodTypeId) {
        form.food_type_id = props.newlyCreatedFoodTypeId.toString();
        form.servings = '1'; // Set default serving amount
        updateSelectedFoodType();
    }
});
</script>

<template>
    <Head title="Daily Food Log" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Daily Food Log
                </h2>
                <a
                    :href="route('foods.index')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg"
                >
                    View All Days
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Date Selector -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <InputLabel for="selected_date" value="Select Date" />
                            <TextInput
                                id="selected_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="selectedDate"
                                required
                            />
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formattedDate }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Totals -->
                <div v-if="foods.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Totals</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ Math.round(dailyTotals?.calories || 0) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Calories</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ Math.round(dailyTotals?.protein || 0) }}g
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Protein</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ Math.round(dailyTotals?.carbs || 0) }}g
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Carbs</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ Math.round(dailyTotals?.fat || 0) }}g
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Fat</div>
                        </div>
                    </div>
                </div>

                <!-- Create New Food Type Form (shown when "Create New Food" is selected) -->
                <div v-if="showCreateFoodType" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-green-200 dark:border-green-700">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Create New Food Type</h3>
                            <button
                                @click="showCreateFoodType = false; form.food_type_id = ''"
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
                                    class="mt-1 block w-full"
                                    v-model="createFoodTypeForm.name"
                                    placeholder="e.g., Refried Beans"
                                    required
                                />
                                <InputError class="mt-2" :message="createFoodTypeForm.errors.name" />
                            </div>


                            <div class="border-t pt-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    Nutrition Facts Per Serving
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <InputLabel for="calories_per_serving" value="Calories *" />
                                        <TextInput
                                            id="calories_per_serving"
                                            type="number"
                                            step="0.1"
                                            class="mt-1 block w-full"
                                            v-model="createFoodTypeForm.calories_per_serving"
                                            placeholder="e.g., 140"
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
                                            class="mt-1 block w-full"
                                            v-model="createFoodTypeForm.protein_per_serving"
                                            placeholder="e.g., 8"
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
                                            class="mt-1 block w-full"
                                            v-model="createFoodTypeForm.carbs_per_serving"
                                            placeholder="e.g., 19"
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
                                            class="mt-1 block w-full"
                                            v-model="createFoodTypeForm.fat_per_serving"
                                            placeholder="e.g., 4.5"
                                            required
                                        />
                                        <InputError class="mt-2" :message="createFoodTypeForm.errors.fat_per_serving" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="description" value="Description (optional)" />
                                <textarea
                                    id="description"
                                    v-model="createFoodTypeForm.description"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    rows="2"
                                    placeholder="Additional details about this food..."
                                ></textarea>
                                <InputError class="mt-2" :message="createFoodTypeForm.errors.description" />
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
                </div>

                <!-- Add New Food Form -->
                <div v-if="!showCreateFoodType" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Food</h3>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="food_type_id" value="Food Type" />
                                <select
                                    id="food_type_id"
                                    v-model="form.food_type_id"
                                    @change="updateSelectedFoodType"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                >
                                    <option value="create-new">➕ Create New Food</option>
                                    <option value="" v-if="foodTypes.length > 0">Select existing food type</option>
                                    <optgroup 
                                        v-for="category in [...new Set(foodTypes.map(ft => ft.category))]"
                                        :key="category"
                                        :label="category"
                                        v-if="foodTypes.length > 0"
                                    >
                                        <option
                                            v-for="type in foodTypes.filter(ft => ft.category === category)"
                                            :key="type.id"
                                            :value="type.id"
                                        >
                                            {{ type.name }}
                                        </option>
                                    </optgroup>
                                </select>
                                <InputError class="mt-2" :message="form.errors.food_type_id" />
                                
                                <!-- Newly created food type notification -->
                                <div v-if="selectedFoodType && props.newlyCreatedFoodTypeId && selectedFoodType.id === props.newlyCreatedFoodTypeId" 
                                     class="mt-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-green-800 dark:text-green-200 font-medium">
                                            ✨ Your new food type "{{ selectedFoodType.name }}" is ready to log! 
                                            Adjust servings below and click "Add Food & Continue".
                                        </span>
                                    </div>
                                </div>
                            </div>


                            <div v-if="selectedFoodType">
                                <InputLabel for="servings" value="Servings" />
                                <TextInput
                                    id="servings"
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    class="mt-1 block w-full"
                                    v-model="form.servings"
                                    placeholder="e.g., 1.5"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.servings" />
                            </div>

                            <div v-if="estimatedNutrition" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                                    Nutrition for {{ estimatedNutrition.displayQuantity }}
                                </h4>
                                <div class="grid grid-cols-4 gap-4 text-sm">
                                    <div class="text-center">
                                        <div class="font-medium text-blue-600 dark:text-blue-400">
                                            {{ estimatedNutrition.calories }}cal
                                        </div>
                                        <div class="text-gray-500">Calories</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium text-blue-600 dark:text-blue-400">
                                            {{ estimatedNutrition.protein }}g
                                        </div>
                                        <div class="text-gray-500">Protein</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium text-blue-600 dark:text-blue-400">
                                            {{ estimatedNutrition.carbs }}g
                                        </div>
                                        <div class="text-gray-500">Carbs</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium text-blue-600 dark:text-blue-400">
                                            {{ estimatedNutrition.fat }}g
                                        </div>
                                        <div class="text-gray-500">Fat</div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedFoodType">
                                <InputLabel for="notes" value="Notes (optional)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    rows="3"
                                    placeholder="Any additional notes about this food..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.notes" />
                            </div>

                            <div v-if="selectedFoodType" class="flex items-center justify-between">
                                <button
                                    type="button"
                                    @click="form.reset(); selectedFoodType = null"
                                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    Clear Form
                                </button>
                                <PrimaryButton 
                                    :disabled="form.processing"
                                    :class="selectedFoodType && props.newlyCreatedFoodTypeId && selectedFoodType.id === props.newlyCreatedFoodTypeId ? 'bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-900' : ''"
                                >
                                    {{ selectedFoodType && props.newlyCreatedFoodTypeId && selectedFoodType.id === props.newlyCreatedFoodTypeId ? '✨ Log New Food Item' : 'Add Food & Continue' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Existing Foods for This Day -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Foods for This Day</h3>
                        
                        <div v-if="foods.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No food entries for this day yet.</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Add your first food above!</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="food in foods"
                                :key="food.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <!-- Regular Display Mode -->
                                <div v-if="editingFoodId !== food.id">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h6 class="font-medium text-gray-900 dark:text-white">
                                                {{ food.food_type.name }}
                                            </h6>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ food.food_type.category }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                {{ displayServings(food.servings) }} serving{{ food.servings !== 1 ? 's' : '' }}
                                            </p>
                                            <p v-if="food.notes" class="text-sm text-gray-500 dark:text-gray-400 mt-1 italic">
                                                {{ food.notes }}
                                            </p>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ Math.round(food.total_calories) }} cal
                                            </div>
                                            <div class="flex space-x-3 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <span>P: {{ Math.round(food.total_protein) }}g</span>
                                                <span>C: {{ Math.round(food.total_carbs) }}g</span>
                                                <span>F: {{ Math.round(food.total_fat) }}g</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex space-x-3">
                                        <button
                                            @click="startEditing(food)"
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
                                            <h6 class="font-medium text-gray-900 dark:text-white">
                                                {{ food.food_type.name }}
                                            </h6>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ food.food_type.category }}
                                            </p>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ getEditedNutrition(food).calories }} cal
                                            </div>
                                            <div class="flex space-x-3 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <span>Protein: {{ getEditedNutrition(food).protein }}g</span>
                                                <span>Carbs: {{ getEditedNutrition(food).carbs }}g</span>
                                                <span>Fat: {{ getEditedNutrition(food).fat }}g</span>
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
                                            @click="cancelEditing"
                                            class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 text-sm font-medium px-3 py-1"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            @click="saveEdit(food)"
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-3 py-1 rounded"
                                        >
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>