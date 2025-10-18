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
    completed: boolean;
    notes: string | null;
}

interface Workout {
    id: number;
    workout_type: {
        id: number;
        name: string;
        description: string;
        muscle_group: string;
        equipment_needed: string;
        sides: string;
    };
    sets: WorkoutSet[];
    notes: string | null;
    performed_at: string;
}

interface Props {
    workout: Workout;
}

const props = defineProps<Props>();

const deleteForm = useForm({});

const deleteWorkout = () => {
    if (confirm('Are you sure you want to delete this workout?')) {
        deleteForm.delete(route('workouts.destroy', props.workout.id));
    }
};

const formatDifficulty = (difficulty: string | null) => {
    if (!difficulty) return '';
    return difficulty.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <Head :title="`${workout.workout_type.name} - ${new Date(workout.performed_at).toLocaleDateString()}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ workout.workout_type.name }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('workouts.create')"
                        :data="{
                            workout_type_id: workout.workout_type.id,
                            performed_at: workout.performed_at.split('T')[0]
                        }"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Add Set
                    </Link>
                    <Link
                        :href="route('workouts.edit', workout.id)"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Edit
                    </Link>
                    <button
                        @click="deleteWorkout"
                        :disabled="deleteForm.processing"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Delete
                    </button>
                    <Link
                        :href="route('workouts.index')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Back to Workouts
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Workout Type Info -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    Workout Details
                                </h3>
                                <div class="space-y-2">
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Type:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ workout.workout_type.name }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Muscle Group:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ workout.workout_type.muscle_group }}</span>
                                    </div>
                                    <div v-if="workout.workout_type.equipment_needed">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Equipment:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ workout.workout_type.equipment_needed }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Date:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">{{ new Date(workout.performed_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Performance Summary -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    Summary
                                </h3>
                                <div class="space-y-2">
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Total Sets:</span>
                                        <span class="ml-2 text-2xl font-bold text-blue-600">{{ workout.sets.length }}</span>
                                    </div>
                                    <div v-if="workout.sets.some(s => s.weight)">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Weight Range:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">
                                            {{ Math.min(...workout.sets.filter(s => s.weight).map(s => s.weight)) }}lbs -
                                            {{ Math.max(...workout.sets.filter(s => s.weight).map(s => s.weight)) }}lbs
                                        </span>
                                    </div>
                                    <div v-if="workout.sets.some(s => s.reps)">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Rep Range:</span>
                                        <span class="ml-2 text-gray-900 dark:text-white">
                                            {{ Math.min(...workout.sets.filter(s => s.reps).map(s => s.reps)) }} -
                                            {{ Math.max(...workout.sets.filter(s => s.reps).map(s => s.reps)) }} reps
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sets -->
                        <div v-if="workout.sets.length > 0" class="mt-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Sets
                                </h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="set in workout.sets"
                                        :key="set.id"
                                        class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded border"
                                    >
                                        <div class="flex items-center space-x-4">
                                            <span class="font-medium text-gray-900 dark:text-white w-12">
                                                Set {{ set.set_number }}
                                            </span>
                                            <div v-if="set.reps" class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ set.reps }} reps
                                            </div>
                                            <div v-if="set.weight" class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ set.weight }}lbs
                                            </div>
                                            <div v-if="set.duration_seconds" class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ set.duration_seconds }}s
                                            </div>
                                            <div v-if="set.difficulty" class="text-xs px-2 py-1 rounded"
                                                :class="{
                                                    'bg-green-100 text-green-800': set.difficulty === 'easy',
                                                    'bg-yellow-100 text-yellow-800': set.difficulty === 'hard',
                                                    'bg-orange-100 text-orange-800': set.difficulty === 'really_hard',
                                                    'bg-red-100 text-red-800': set.difficulty === 'almost_fail',
                                                    'bg-red-200 text-red-900': set.difficulty === 'fail'
                                                }">
                                                {{ formatDifficulty(set.difficulty) }}
                                            </div>
                                        </div>
                                        <div v-if="set.notes" class="text-sm text-gray-500 dark:text-gray-400 italic">
                                            {{ set.notes }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="workout.notes" class="mt-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    Notes
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ workout.notes }}</p>
                            </div>
                        </div>

                        <!-- Workout Type Description -->
                        <div v-if="workout.workout_type.description" class="mt-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                    About This Exercise
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ workout.workout_type.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>