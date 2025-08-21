<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Food {
    id: number;
    food_type: {
        id: number;
        name: string;
        category: string;
    };
    quantity_grams: number;
    total_calories: number;
    total_protein: number;
    total_carbs: number;
    total_fat: number;
    consumed_at: string;
}

interface Props {
    foods: {
        data: Food[];
        links: any[];
        meta: any;
    };
}

defineProps<Props>();
</script>

<template>
    <Head title="Food Log" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Your Food Log
                </h2>
                <Link
                    href="/foods/create"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
                >
                    Log New Food
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="foods.data.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No food entries logged yet.</p>
                            <Link
                                href="/foods/create"
                                class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
                            >
                                Log Your First Meal
                            </Link>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="food in foods.data"
                                :key="food.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ food.food_type.name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ food.food_type.category }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                            {{ new Date(food.consumed_at).toLocaleDateString() }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_calories) }} cal
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ food.quantity_grams }}g
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                                    <div class="text-center">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_protein) }}g
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400">Protein</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_carbs) }}g
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400">Carbs</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_fat) }}g
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400">Fat</div>
                                    </div>
                                </div>
                                <div class="mt-3 flex space-x-2">
                                    <Link
                                        :href="`/foods/${food.id}`"
                                        class="text-green-600 hover:text-green-800 text-sm"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="`/foods/${food.id}/edit`"
                                        class="text-blue-600 hover:text-blue-800 text-sm"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination would go here -->
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>