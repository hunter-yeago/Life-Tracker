<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface FoodType {
    id: number;
    name: string;
    description: string;
    calories_per_100g: number;
    protein_per_100g: number;
    carbs_per_100g: number;
    fat_per_100g: number;
    category: string;
}

interface Props {
    foodTypes: FoodType[];
}

const props = defineProps<Props>();

const form = useForm({
    food_type_id: '',
    quantity_grams: '',
    consumed_at: new Date().toISOString().split('T')[0],
    notes: '',
});

const selectedFoodType = ref<FoodType | null>(null);

const updateSelectedFoodType = () => {
    const foodTypeId = parseInt(form.food_type_id);
    selectedFoodType.value = props.foodTypes.find(ft => ft.id === foodTypeId) || null;
};

const estimatedNutrition = computed(() => {
    if (!selectedFoodType.value || !form.quantity_grams) {
        return null;
    }

    const quantity = parseFloat(form.quantity_grams);
    const multiplier = quantity / 100;

    return {
        calories: Math.round(selectedFoodType.value.calories_per_100g * multiplier),
        protein: Math.round(selectedFoodType.value.protein_per_100g * multiplier * 10) / 10,
        carbs: Math.round(selectedFoodType.value.carbs_per_100g * multiplier * 10) / 10,
        fat: Math.round(selectedFoodType.value.fat_per_100g * multiplier * 10) / 10,
    };
});

const submit = () => {
    form.post(route('foods.store'));
};
</script>

<template>
    <Head title="Log Food" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Log New Food
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
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
                                    Nutrition per 100g
                                </h4>
                                <div class="grid grid-cols-4 gap-4 text-sm">
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.calories_per_100g }}cal</div>
                                        <div class="text-gray-500">Calories</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.protein_per_100g }}g</div>
                                        <div class="text-gray-500">Protein</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.carbs_per_100g }}g</div>
                                        <div class="text-gray-500">Carbs</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium">{{ selectedFoodType.fat_per_100g }}g</div>
                                        <div class="text-gray-500">Fat</div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="quantity_grams" value="Quantity (grams)" />
                                <TextInput
                                    id="quantity_grams"
                                    type="number"
                                    step="0.1"
                                    class="mt-1 block w-full"
                                    v-model="form.quantity_grams"
                                    placeholder="e.g., 150"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.quantity_grams" />
                            </div>

                            <div v-if="estimatedNutrition" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                                    Estimated Nutrition for {{ form.quantity_grams }}g
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
                                <InputLabel for="consumed_at" value="Date Consumed" />
                                <TextInput
                                    id="consumed_at"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.consumed_at"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.consumed_at" />
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

                            <div class="flex items-center justify-end">
                                <PrimaryButton class="ms-4" :disabled="form.processing">
                                    Log Food
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>