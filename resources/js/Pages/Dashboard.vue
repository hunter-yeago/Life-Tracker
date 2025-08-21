<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import * as d3 from 'd3';

interface Props {
    workoutStats: {
        totalWorkouts: number;
        workoutsByDay: Array<{ date: string; count: number }>;
        workoutsByType: Record<string, number>;
        totalSets: number;
        totalReps: number;
    };
    nutritionStats: {
        totalCalories: number;
        totalProtein: number;
        totalCarbs: number;
        totalFat: number;
        caloriesByDay: Array<{ date: string; calories: number }>;
        macrosByDay: Array<{ date: string; protein: number; carbs: number; fat: number }>;
        averageDailyCalories: number;
    };
    recentActivity: {
        workouts: Array<any>;
        foods: Array<any>;
    };
}

const props = defineProps<Props>();

const workoutChart = ref<HTMLDivElement>();
const calorieChart = ref<HTMLDivElement>();

onMounted(() => {
    if (workoutChart.value && props.workoutStats.workoutsByDay.length > 0) {
        createWorkoutChart();
    }
    if (calorieChart.value && props.nutritionStats.caloriesByDay.length > 0) {
        createCalorieChart();
    }
});

function createWorkoutChart() {
    if (!workoutChart.value) return;
    
    const margin = { top: 20, right: 30, bottom: 40, left: 40 };
    const width = 400 - margin.left - margin.right;
    const height = 200 - margin.bottom - margin.top;

    const svg = d3.select(workoutChart.value)
        .append('svg')
        .attr('width', width + margin.left + margin.right)
        .attr('height', height + margin.top + margin.bottom)
        .append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    const data = props.workoutStats.workoutsByDay;
    
    const x = d3.scaleBand()
        .domain(data.map((d: any) => d.date))
        .range([0, width])
        .padding(0.1);

    const y = d3.scaleLinear()
        .domain([0, d3.max(data, (d: any) => d.count) || 0])
        .range([height, 0]);

    svg.selectAll('.bar')
        .data(data)
        .enter().append('rect')
        .attr('class', 'bar')
        .attr('x', (d: any) => x(d.date) || 0)
        .attr('width', x.bandwidth())
        .attr('y', (d: any) => y(d.count))
        .attr('height', (d: any) => height - y(d.count))
        .attr('fill', '#3B82F6');

    svg.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x));

    svg.append('g')
        .call(d3.axisLeft(y));
}

function createCalorieChart() {
    if (!calorieChart.value) return;
    
    const margin = { top: 20, right: 30, bottom: 40, left: 40 };
    const width = 400 - margin.left - margin.right;
    const height = 200 - margin.bottom - margin.top;

    const svg = d3.select(calorieChart.value)
        .append('svg')
        .attr('width', width + margin.left + margin.right)
        .attr('height', height + margin.top + margin.bottom)
        .append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    const data = props.nutritionStats.caloriesByDay;
    
    const x = d3.scaleBand()
        .domain(data.map((d: any) => d.date))
        .range([0, width])
        .padding(0.1);

    const y = d3.scaleLinear()
        .domain([0, d3.max(data, (d: any) => d.calories) || 0])
        .range([height, 0]);

    svg.selectAll('.bar')
        .data(data)
        .enter().append('rect')
        .attr('class', 'bar')
        .attr('x', (d: any) => x(d.date) || 0)
        .attr('width', x.bandwidth())
        .attr('y', (d: any) => y(d.calories))
        .attr('height', (d: any) => height - y(d.calories))
        .attr('fill', '#10B981');

    svg.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x));

    svg.append('g')
        .call(d3.axisLeft(y));
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Life Tracker Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Quick Actions -->
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Link
                        href="/workouts/create"
                        class="rounded-lg bg-blue-600 p-6 text-center text-white shadow-sm hover:bg-blue-700"
                    >
                        <h3 class="text-lg font-semibold">Log Workout</h3>
                        <p class="text-sm opacity-90">Record your latest workout session</p>
                    </Link>
                    <Link
                        href="/foods/create"
                        class="rounded-lg bg-green-600 p-6 text-center text-white shadow-sm hover:bg-green-700"
                    >
                        <h3 class="text-lg font-semibold">Log Food</h3>
                        <p class="text-sm opacity-90">Track what you've eaten</p>
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Workouts</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ workoutStats.totalWorkouts }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sets</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ workoutStats.totalSets }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Calories</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ Math.round(nutritionStats.totalCalories) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Daily Calories</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ Math.round(nutritionStats.averageDailyCalories) }}</p>
                    </div>
                </div>

                <!-- Charts -->
                <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Workouts by Day</h3>
                        <div ref="workoutChart"></div>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Calories by Day</h3>
                        <div ref="calorieChart"></div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Workouts</h3>
                            <Link href="/workouts" class="text-blue-600 hover:text-blue-800">View All</Link>
                        </div>
                        <div class="space-y-3">
                            <div v-for="workout in recentActivity.workouts" :key="workout.id" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">{{ workout.workout_type.name }}</span>
                                <span class="text-sm text-gray-500">{{ new Date(workout.performed_at).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="recentActivity.workouts.length === 0" class="text-gray-500">No recent workouts</div>
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Foods</h3>
                            <Link href="/foods" class="text-green-600 hover:text-green-800">View All</Link>
                        </div>
                        <div class="space-y-3">
                            <div v-for="food in recentActivity.foods" :key="food.id" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">{{ food.food_type.name }}</span>
                                <span class="text-sm text-gray-500">{{ Math.round(food.total_calories) }} cal</span>
                            </div>
                            <div v-if="recentActivity.foods.length === 0" class="text-gray-500">No recent foods logged</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
