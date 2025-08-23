<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Food {
    id: number;
    food_type: {
        id: number;
        name: string;
        category: string;
    };
    servings: number;
    quantity_grams: number;
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

interface MonthOption {
    value: string;
    label: string;
}

interface Props {
    foods: Food[];
    dailyTotals: DailyTotals;
    selectedDate: string;
    selectedMonth: string;
    availableDates: string[];
    datesWithData: string[];
    monthOptions: MonthOption[];
}

const props = defineProps<Props>();

function changeMonth(month: string) {
    // When month changes, reset to first available date of that month
    router.get(route('foods.index'), { month }, { preserveState: true });
}

function changeDate(date: string) {
    router.get(route('foods.index'), { 
        month: props.selectedMonth, 
        date 
    }, { preserveState: true });
}

const formattedDate = computed(() => {
    const date = new Date(props.selectedDate);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
});

// Sort foods by consumed_at time
const sortedFoods = computed(() => {
    return [...props.foods].sort((a, b) => {
        return new Date(a.consumed_at).getTime() - new Date(b.consumed_at).getTime();
    });
});
</script>

<template>
    <Head title="Food Log" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Daily Food Log
                </h2>
                <Link
                    :href="route('foods.create', { date: selectedDate })"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
                >
                    Log New Food
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Date Filters -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Month Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Month
                            </label>
                            <select 
                                :value="selectedMonth"
                                @change="changeMonth(($event.target as HTMLSelectElement).value)"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Day Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Day
                            </label>
                            <select 
                                :value="selectedDate"
                                @change="changeDate(($event.target as HTMLSelectElement).value)"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option v-for="date in availableDates" :key="date" :value="date">
                                    {{ new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                    {{ datesWithData.includes(date) ? '' : ' (no data)' }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Selected Date Header -->
                <div class="mb-6 text-center">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ formattedDate }}
                    </h3>
                </div>

                <!-- Daily Totals -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
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

                <!-- Food Entries -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Food Entries</h4>
                        
                        <div v-if="foods.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No food entries for this day.</p>
                            <Link
                                :href="route('foods.create', { date: selectedDate })"
                                class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
                            >
                                Log Your First Meal
                            </Link>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Food Item
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Servings
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Calories
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Protein (g)
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Carbs (g)
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Fat (g)
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="food in sortedFoods" :key="food.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ food.food_type.name }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ food.food_type.category }}
                                                </div>
                                                <div v-if="food.notes" class="text-xs text-gray-500 dark:text-gray-400 italic mt-1">
                                                    {{ food.notes }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                                            {{ food.servings }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_calories) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_protein) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_carbs) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                            {{ Math.round(food.total_fat) }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <Link
                                                    :href="route('foods.show', food.id)"
                                                    class="text-green-600 hover:text-green-800 text-xs font-medium"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('foods.edit', food.id)"
                                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                                                >
                                                    Edit
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Total Row -->
                                    <tr class="bg-gray-50 dark:bg-gray-700 font-semibold">
                                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                                            <strong>TOTAL</strong>
                                        </td>
                                        <td class="px-4 py-3"></td>
                                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                            <strong>{{ Math.round(dailyTotals?.calories || 0) }}</strong>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                            <strong>{{ Math.round(dailyTotals?.protein || 0) }}</strong>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                            <strong>{{ Math.round(dailyTotals?.carbs || 0) }}</strong>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                            <strong>{{ Math.round(dailyTotals?.fat || 0) }}</strong>
                                        </td>
                                        <td class="px-4 py-3"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>