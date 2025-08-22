<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    total_calories: number;
    total_protein: number;
    total_carbs: number;
    total_fat: number;
    consumed_at: string;
    notes?: string;
}

interface Props {
    food: Food;
}

const props = defineProps<Props>();

const form = useForm({
    servings: props.food.servings.toString(),
    notes: props.food.notes || '',
});

const estimatedNutrition = computed(() => {
    const multiplier = parseFloat(form.servings) || 0;
    const servingLabel = props.food.food_type.serving_size || 'serving';
    const displayQuantity = `${form.servings} ${servingLabel}${multiplier !== 1 ? 's' : ''}`;

    return {
        displayQuantity,
        calories: Math.round(props.food.food_type.calories_per_serving * multiplier),
        protein: Math.round(props.food.food_type.protein_per_serving * multiplier * 10) / 10,
        carbs: Math.round(props.food.food_type.carbs_per_serving * multiplier * 10) / 10,
        fat: Math.round(props.food.food_type.fat_per_serving * multiplier * 10) / 10,
    };
});

const submit = () => {
    form.put(route('foods.update', props.food.id));
};

const cancel = () => {
    const consumedDate = new Date(props.food.consumed_at).toISOString().split('T')[0];
    router.visit(route('foods.create', { date: consumedDate }));
};

const formattedDate = computed(() => {
    const date = new Date(props.food.consumed_at);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
});
</script>

<template>
    <Head title="Edit Food Entry" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Food Entry
                </h2>
                <button
                    @click="cancel"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg"
                >
                    Back to {{ formattedDate }}
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ food.food_type.name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ food.food_type.category }} • {{ formattedDate }}
                        </p>
                    </div>

                    <!-- Food Type Nutrition Info -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                            Nutrition per {{ food.food_type.serving_size || 'serving' }}
                        </h4>
                        <div class="grid grid-cols-4 gap-4 text-sm">
                            <div class="text-center">
                                <div class="font-medium">{{ food.food_type.calories_per_serving }}cal</div>
                                <div class="text-gray-500">Calories</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium">{{ food.food_type.protein_per_serving }}g</div>
                                <div class="text-gray-500">Protein</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium">{{ food.food_type.carbs_per_serving }}g</div>
                                <div class="text-gray-500">Carbs</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium">{{ food.food_type.fat_per_serving }}g</div>
                                <div class="text-gray-500">Fat</div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel 
                                for="servings" 
                                :value="`Servings (${food.food_type.serving_size || 'serving'})`" 
                            />
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

                        <div v-if="estimatedNutrition && form.servings" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                                Updated Nutrition for {{ estimatedNutrition.displayQuantity }}
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

                        <div class="flex items-center justify-between pt-4">
                            <button
                                type="button"
                                @click="cancel"
                                class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                Cancel
                            </button>
                            <PrimaryButton :disabled="form.processing">
                                Update Entry
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>