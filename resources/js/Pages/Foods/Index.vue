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
    <Head title="Food Log" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Daily Food Log
                </h2>
                <Link
                    :href="route('foods.create')"
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
                                :href="route('foods.create')"
                                class="mt-4 inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
                            >
                                Log Your First Meal
                            </Link>
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
                                                    {{ food.quantity_grams }}g
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
                                            <Link
                                                :href="route('foods.show', food.id)"
                                                class="text-green-600 hover:text-green-800 text-sm font-medium"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                :href="route('foods.edit', food.id)"
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                            >
                                                Edit
                                            </Link>
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