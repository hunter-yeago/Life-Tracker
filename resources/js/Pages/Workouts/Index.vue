<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Workout {
    id: number;
    workout_type: {
        id: number;
        name: string;
        muscle_group: string;
    };
    sets: number;
    reps: number;
    weight: number;
    duration_minutes: number;
    distance: number;
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
                    href="/workouts/create"
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
                                href="/workouts/create"
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
                                        <div v-if="workout.sets" class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ workout.sets }} sets × {{ workout.reps }} reps
                                        </div>
                                        <div v-if="workout.weight" class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ workout.weight }}kg
                                        </div>
                                        <div v-if="workout.duration_minutes" class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ workout.duration_minutes }} minutes
                                        </div>
                                        <div v-if="workout.distance" class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ workout.distance }}km
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex space-x-2">
                                    <Link
                                        :href="`/workouts/${workout.id}`"
                                        class="text-blue-600 hover:text-blue-800 text-sm"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="`/workouts/${workout.id}/edit`"
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