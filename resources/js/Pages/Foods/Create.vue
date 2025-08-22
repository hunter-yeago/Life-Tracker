<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
}

const props = defineProps<Props>();

const selectedDate = ref(props.selectedDate);

const form = useForm({
    food_type_id: '',
    servings: '',
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

const updateSelectedFoodType = () => {
    const foodTypeId = parseInt(form.food_type_id);
    selectedFoodType.value = props.foodTypes.find(ft => ft.id === foodTypeId) || null;
};

const estimatedNutrition = computed(() => {
    if (!selectedFoodType.value || !form.servings) {
        return null;
    }

    const multiplier = parseFloat(form.servings);
    const servingLabel = selectedFoodType.value.serving_size || 'serving';
    const displayQuantity = `${form.servings} ${servingLabel}${multiplier !== 1 ? 's' : ''}`;

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
            // Reload page to show updated food list
            router.reload();
        }
    });
};

const deleteFood = (foodId: number) => {
    if (confirm('Are you sure you want to delete this food entry?')) {
        router.delete(route('foods.destroy', foodId), {
            onSuccess: () => {
                router.reload();
            }
        });
    }
};

const formattedDate = computed(() => {
    const date = new Date(selectedDate.value);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
});

const groupedFoods = computed(() => {
    const groups: { [key: string]: Food[] } = {};
    
    props.foods.forEach(food => {
        const time = new Date(food.consumed_at).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        if (!groups[time]) {
            groups[time] = [];
        }
        groups[time].push(food);
    });
    
    return Object.entries(groups).sort((a, b) => {
        const timeA = new Date(`2000-01-01 ${a[0]}`);
        const timeB = new Date(`2000-01-01 ${b[0]}`);
        return timeA.getTime() - timeB.getTime();
    });
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

                <!-- Add New Food Form -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
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
                                    <option value="">Select a food type</option>
                                    <optgroup 
                                        v-for="category in [...new Set(foodTypes.map(ft => ft.category))]"
                                        :key="category"
                                        :label="category"
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
                            </div>

                            <div v-if="selectedFoodType" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                                    Nutrition per {{ selectedFoodType.serving_size || 'serving' }}
                                    <span v-if="selectedFoodType.serving_weight_grams" class="text-sm text-gray-500">
                                        ({{ selectedFoodType.serving_weight_grams }}g)
                                    </span>
                                </h4>
                                <div class="grid grid-cols-4 gap-4 text-sm">
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.calories_per_serving }}cal</div>
                                        <div class="text-gray-500">Calories</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.protein_per_serving }}g</div>
                                        <div class="text-gray-500">Protein</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.carbs_per_serving }}g</div>
                                        <div class="text-gray-500">Carbs</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.fat_per_serving }}g</div>
                                        <div class="text-gray-500">Fat</div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="servings" :value="`Servings ${selectedFoodType ? `(${selectedFoodType.serving_size || 'serving'})` : ''}`" />
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
                                    Estimated Nutrition for {{ estimatedNutrition.displayQuantity }}
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


                            <div>
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

                            <div class="flex items-center justify-between">
                                <button
                                    type="button"
                                    @click="form.reset()"
                                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    Clear Form
                                </button>
                                <PrimaryButton :disabled="form.processing">
                                    Add Food & Continue
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

                        <div v-else class="space-y-6">
                            <div v-for="[time, timeGroupFoods] in groupedFoods" :key="time" class="border-l-4 border-green-500 pl-4">
                                <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">{{ time }}</h5>
                                <div class="space-y-3">
                                    <div
                                        v-for="food in timeGroupFoods"
                                        :key="food.id"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h6 class="font-medium text-gray-900 dark:text-white">
                                                    {{ food.food_type.name }}
                                                </h6>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ food.food_type.category }}
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                    {{ food.servings }} {{ food.food_type.serving_size || 'serving' }}{{ food.servings !== 1 ? 's' : '' }}
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
                                                @click="router.visit(route('foods.edit', food.id))"
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>