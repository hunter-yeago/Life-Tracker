<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface WorkoutSet {
    id: number;
    set_number: number;
    reps: number | null;
    weight: number | null;
    duration_seconds: number | null;
    difficulty: string | null;
}

interface Workout {
    id: number;
    workout_type: {
        id: number;
        name: string;
        muscle_group: string;
    };
    sets: WorkoutSet[];
    notes: string | null;
    performed_at: string;
}

interface Props {
    workouts: {
        data: Workout[];
        links: any[];
        meta: any;
    };
}

defineProps<Props>();

const formatDifficulty = (difficulty: string | null) => {
    if (!difficulty) return '';
    return difficulty.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <Head title="Workouts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Your Workouts
                </h2>
                <Link
                    :href="route('workouts.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                >
                    Log New Workout
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="workouts.data.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No workouts logged yet.</p>
                            <Link
                                :href="route('workouts.create')"
                                class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                            >
                                Log Your First Workout
                            </Link>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="workout in workouts.data"
                                :key="workout.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ workout.workout_type.name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ workout.workout_type.muscle_group }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                            {{ new Date(workout.performed_at).toLocaleDateString() }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ workout.sets.length }} sets
                                        </div>
                                        <div v-if="workout.sets.some(s => s.weight)" class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ Math.min(...workout.sets.filter(s => s.weight).map(s => s.weight)) }} -
                                            {{ Math.max(...workout.sets.filter(s => s.weight).map(s => s.weight)) }}lbs
                                        </div>
                                        <div v-if="workout.sets.some(s => s.reps)" class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ Math.min(...workout.sets.filter(s => s.reps).map(s => s.reps)) }} -
                                            {{ Math.max(...workout.sets.filter(s => s.reps).map(s => s.reps)) }} reps
                                        </div>
                                        <div v-if="workout.sets.some(s => s.difficulty)" class="text-xs text-gray-500 dark:text-gray-400">
                                            Best: {{ formatDifficulty(workout.sets.filter(s => s.difficulty).sort((a, b) => {
                                                const order = ['easy', 'hard', 'really_hard', 'almost_fail', 'fail'];
                                                return order.indexOf(a.difficulty) - order.indexOf(b.difficulty);
                                            })[0]?.difficulty) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex space-x-2">
                                    <Link
                                        :href="route('workouts.show', workout.id)"
                                        class="text-blue-600 hover:text-blue-800 text-sm"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="route('workouts.edit', workout.id)"
                                        class="text-green-600 hover:text-green-800 text-sm"
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