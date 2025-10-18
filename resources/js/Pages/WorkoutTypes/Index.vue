<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface WorkoutType {
    id: number;
    name: string;
    description: string;
    muscle_group: string;
    equipment_needed: string;
    sides: string;
    workouts_count: number;
}

interface Props {
    workoutTypes: {
        data: WorkoutType[];
        links: any[];
        meta: any;
    };
}

defineProps<Props>();

const deleteForm = useForm({});

const deleteWorkoutType = (workoutType: WorkoutType) => {
    if (workoutType.workouts_count > 0) {
        alert('Cannot delete workout type that has associated workouts.');
        return;
    }

    if (confirm(`Are you sure you want to delete "${workoutType.name}"?`)) {
        deleteForm.delete(route('workout-types.destroy', workoutType.id));
    }
};

const formatSides = (sides: string) => {
    return sides === 'both' ? 'Both Sides' : sides === 'separate' ? 'Left/Right Separate' : 'No Sides';
};
</script>

<template>
    <Head title="Workout Types" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Workout Types
                </h2>
                <Link
                    :href="route('workout-types.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                >
                    Add New Type
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="workoutTypes.data.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No workout types created yet.</p>
                            <Link
                                :href="route('workout-types.create')"
                                class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                            >
                                Create Your First Workout Type
                            </Link>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="workoutType in workoutTypes.data"
                                :key="workoutType.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ workoutType.name }}
                                            </h3>
                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                                {{ workoutType.muscle_group }}
                                            </span>
                                            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded-full">
                                                {{ formatSides(workoutType.sides) }}
                                            </span>
                                        </div>

                                        <p v-if="workoutType.description" class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                            {{ workoutType.description }}
                                        </p>

                                        <div class="flex items-center space-x-4 mt-2">
                                            <span v-if="workoutType.equipment_needed" class="text-sm text-gray-500 dark:text-gray-400">
                                                Equipment: {{ workoutType.equipment_needed }}
                                            </span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ workoutType.workouts_count }} workouts logged
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Link
                                            :href="route('workout-types.show', workoutType.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('workout-types.edit', workoutType.id)"
                                            class="text-green-600 hover:text-green-800 text-sm"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteWorkoutType(workoutType)"
                                            :disabled="deleteForm.processing || workoutType.workouts_count > 0"
                                            :class="{
                                                'text-red-600 hover:text-red-800': workoutType.workouts_count === 0,
                                                'text-gray-400 cursor-not-allowed': workoutType.workouts_count > 0
                                            }"
                                            class="text-sm"
                                        >
                                            Delete
                                        </button>
                                    </div>
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