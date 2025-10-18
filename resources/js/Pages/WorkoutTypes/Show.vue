<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface WorkoutType {
    id: number;
    name: string;
    description: string | null;
    muscle_group: string;
    equipment_needed: string | null;
    sides: string;
    workouts: Array<{
        id: number;
        performed_at: string;
        user: {
            name: string;
        };
    }>;
}

interface Props {
    workoutType: WorkoutType;
}

const props = defineProps<Props>();

const deleteForm = useForm({});

const deleteWorkoutType = () => {
    if (props.workoutType.workouts.length > 0) {
        alert('Cannot delete workout type that has associated workouts.');
        return;
    }

    if (confirm(`Are you sure you want to delete "${props.workoutType.name}"?`)) {
        deleteForm.delete(route('workout-types.destroy', props.workoutType.id));
    }
};

const formatSides = (sides: string) => {
    return sides === 'both' ? 'Both Sides' : sides === 'separate' ? 'Left/Right Separate' : 'No Sides';
};
</script>

<template>
    <Head :title="workoutType.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ workoutType.name }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('workout-types.edit', workoutType.id)"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Edit
                    </Link>
                    <button
                        @click="deleteWorkoutType"
                        :disabled="deleteForm.processing || workoutType.workouts.length > 0"
                        :class="{
                            'bg-red-600 hover:bg-red-700': workoutType.workouts.length === 0,
                            'bg-gray-400 cursor-not-allowed': workoutType.workouts.length > 0
                        }"
                        class="text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Delete
                    </button>
                    <Link
                        :href="route('workout-types.index')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Workout Type Details -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    Workout Details
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Name:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ workoutType.name }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Muscle Group:</span>
                                        <span class="ml-2 inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">
                                            {{ workoutType.muscle_group }}
                                        </span>
                                    </div>
                                    <div v-if="workoutType.equipment_needed">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Equipment:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ workoutType.equipment_needed }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Side Support:</span>
                                        <span class="ml-2 inline-block bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm">
                                            {{ formatSides(workoutType.sides) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Usage Statistics -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    Usage Statistics
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Total Workouts:</span>
                                        <span class="ml-2 text-2xl font-bold text-blue-600">{{ workoutType.workouts.length }}</span>
                                    </div>
                                    <div v-if="workoutType.workouts.length > 0">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Last Used:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">
                                            {{ new Date(workoutType.workouts[0].performed_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="workoutType.description" class="mt-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    Description
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ workoutType.description }}</p>
                            </div>
                        </div>

                        <!-- Recent Workouts -->
                        <div v-if="workoutType.workouts.length > 0" class="mt-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    Recent Workouts ({{ workoutType.workouts.length }} total)
                                </h3>
                                <div class="space-y-2">
                                    <div
                                        v-for="workout in workoutType.workouts.slice(0, 10)"
                                        :key="workout.id"
                                        class="flex justify-between items-center py-2 px-3 bg-white dark:bg-gray-800 rounded"
                                    >
                                        <span class="text-gray-900 dark:text-white">
                                            {{ new Date(workout.performed_at).toLocaleDateString() }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            by {{ workout.user.name }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="workoutType.workouts.length > 10" class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                    ... and {{ workoutType.workouts.length - 10 }} more
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex justify-center">
                            <Link
                                :href="route('workouts.create')"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg"
                            >
                                Log {{ workoutType.name }} Workout
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>