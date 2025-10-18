<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface WorkoutType {
    id: number;
    name: string;
    description: string;
    muscle_group: string;
    equipment_needed: string;
    sides: string;
}

interface Workout {
    id: number;
    workout_type_id: number;
    sets: number | null;
    reps: number | null;
    weight: number | null;
    duration_minutes: number | null;
    difficulty: string | null;
    left_sets: number | null;
    left_reps: number | null;
    left_weight: number | null;
    left_difficulty: string | null;
    right_sets: number | null;
    right_reps: number | null;
    right_weight: number | null;
    right_difficulty: string | null;
    notes: string | null;
    performed_at: string;
}

interface Props {
    workout: Workout;
    workoutTypes: WorkoutType[];
}

const props = defineProps<Props>();

const form = useForm({
    workout_type_id: props.workout.workout_type_id.toString(),
    sets: props.workout.sets?.toString() || '',
    reps: props.workout.reps?.toString() || '',
    weight: props.workout.weight?.toString() || '',
    duration_minutes: props.workout.duration_minutes?.toString() || '',
    difficulty: props.workout.difficulty || '',
    left_sets: props.workout.left_sets?.toString() || '',
    left_reps: props.workout.left_reps?.toString() || '',
    left_weight: props.workout.left_weight?.toString() || '',
    left_difficulty: props.workout.left_difficulty || '',
    right_sets: props.workout.right_sets?.toString() || '',
    right_reps: props.workout.right_reps?.toString() || '',
    right_weight: props.workout.right_weight?.toString() || '',
    right_difficulty: props.workout.right_difficulty || '',
    notes: props.workout.notes || '',
    performed_at: props.workout.performed_at.split('T')[0],
});

const submit = () => {
    form.put(route('workouts.update', props.workout.id));
};

const selectedWorkoutType = computed(() => {
    return props.workoutTypes.find(type => type.id.toString() === form.workout_type_id);
});

const supportsSides = computed(() => {
    return selectedWorkoutType.value?.sides === 'both' || selectedWorkoutType.value?.sides === 'separate';
});
</script>

<template>
    <Head title="Edit Workout" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Workout
                </h2>
                <Link
                    :href="route('workouts.show', workout.id)"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm"
                >
                    Cancel
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="workout_type_id" value="Workout Type" />
                                <select
                                    id="workout_type_id"
                                    v-model="form.workout_type_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                >
                                    <option value="">Select a workout type</option>
                                    <option
                                        v-for="type in workoutTypes"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.name }} ({{ type.muscle_group }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.workout_type_id" />
                            </div>

                            <!-- Basic Workout Data -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">General Workout Data</h3>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <InputLabel for="sets" value="Sets" />
                                        <TextInput
                                            id="sets"
                                            type="number"
                                            class="mt-1 block w-full"
                                            v-model="form.sets"
                                            placeholder="e.g., 3"
                                        />
                                        <InputError class="mt-2" :message="form.errors.sets" />
                                    </div>

                                    <div>
                                        <InputLabel for="reps" value="Reps" />
                                        <TextInput
                                            id="reps"
                                            type="number"
                                            class="mt-1 block w-full"
                                            v-model="form.reps"
                                            placeholder="e.g., 10"
                                        />
                                        <InputError class="mt-2" :message="form.errors.reps" />
                                    </div>

                                    <div>
                                        <InputLabel for="weight" value="Weight (kg)" />
                                        <TextInput
                                            id="weight"
                                            type="number"
                                            step="0.1"
                                            class="mt-1 block w-full"
                                            v-model="form.weight"
                                            placeholder="e.g., 80.5"
                                        />
                                        <InputError class="mt-2" :message="form.errors.weight" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <InputLabel for="duration_minutes" value="Duration (minutes)" />
                                        <TextInput
                                            id="duration_minutes"
                                            type="number"
                                            class="mt-1 block w-full"
                                            v-model="form.duration_minutes"
                                            placeholder="e.g., 30"
                                        />
                                        <InputError class="mt-2" :message="form.errors.duration_minutes" />
                                    </div>

                                    <div>
                                        <InputLabel for="difficulty" value="Difficulty" />
                                        <select
                                            id="difficulty"
                                            v-model="form.difficulty"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select difficulty (optional)</option>
                                            <option value="easy">Easy</option>
                                            <option value="hard">Hard</option>
                                            <option value="really_hard">Really Hard</option>
                                            <option value="almost_fail">Almost Fail</option>
                                            <option value="fail">Fail</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.difficulty" />
                                    </div>
                                </div>
                            </div>

                            <!-- Left/Right Side Specific Data -->
                            <div v-if="supportsSides" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                    <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-4">Left Side</h3>

                                    <div class="space-y-4">
                                        <div>
                                            <InputLabel for="left_sets" value="Left Sets" />
                                            <TextInput
                                                id="left_sets"
                                                type="number"
                                                class="mt-1 block w-full"
                                                v-model="form.left_sets"
                                                placeholder="e.g., 3"
                                            />
                                            <InputError class="mt-2" :message="form.errors.left_sets" />
                                        </div>

                                        <div>
                                            <InputLabel for="left_reps" value="Left Reps" />
                                            <TextInput
                                                id="left_reps"
                                                type="number"
                                                class="mt-1 block w-full"
                                                v-model="form.left_reps"
                                                placeholder="e.g., 10"
                                            />
                                            <InputError class="mt-2" :message="form.errors.left_reps" />
                                        </div>

                                        <div>
                                            <InputLabel for="left_weight" value="Left Weight (kg)" />
                                            <TextInput
                                                id="left_weight"
                                                type="number"
                                                step="0.1"
                                                class="mt-1 block w-full"
                                                v-model="form.left_weight"
                                                placeholder="e.g., 80.5"
                                            />
                                            <InputError class="mt-2" :message="form.errors.left_weight" />
                                        </div>

                                        <div>
                                            <InputLabel for="left_difficulty" value="Left Difficulty" />
                                            <select
                                                id="left_difficulty"
                                                v-model="form.left_difficulty"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            >
                                                <option value="">Select difficulty (optional)</option>
                                                <option value="easy">Easy</option>
                                                <option value="hard">Hard</option>
                                                <option value="really_hard">Really Hard</option>
                                                <option value="almost_fail">Almost Fail</option>
                                                <option value="fail">Fail</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.left_difficulty" />
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-green-200 dark:border-green-700 rounded-lg p-4">
                                    <h3 class="text-lg font-medium text-green-900 dark:text-green-100 mb-4">Right Side</h3>

                                    <div class="space-y-4">
                                        <div>
                                            <InputLabel for="right_sets" value="Right Sets" />
                                            <TextInput
                                                id="right_sets"
                                                type="number"
                                                class="mt-1 block w-full"
                                                v-model="form.right_sets"
                                                placeholder="e.g., 3"
                                            />
                                            <InputError class="mt-2" :message="form.errors.right_sets" />
                                        </div>

                                        <div>
                                            <InputLabel for="right_reps" value="Right Reps" />
                                            <TextInput
                                                id="right_reps"
                                                type="number"
                                                class="mt-1 block w-full"
                                                v-model="form.right_reps"
                                                placeholder="e.g., 10"
                                            />
                                            <InputError class="mt-2" :message="form.errors.right_reps" />
                                        </div>

                                        <div>
                                            <InputLabel for="right_weight" value="Right Weight (kg)" />
                                            <TextInput
                                                id="right_weight"
                                                type="number"
                                                step="0.1"
                                                class="mt-1 block w-full"
                                                v-model="form.right_weight"
                                                placeholder="e.g., 80.5"
                                            />
                                            <InputError class="mt-2" :message="form.errors.right_weight" />
                                        </div>

                                        <div>
                                            <InputLabel for="right_difficulty" value="Right Difficulty" />
                                            <select
                                                id="right_difficulty"
                                                v-model="form.right_difficulty"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            >
                                                <option value="">Select difficulty (optional)</option>
                                                <option value="easy">Easy</option>
                                                <option value="hard">Hard</option>
                                                <option value="really_hard">Really Hard</option>
                                                <option value="almost_fail">Almost Fail</option>
                                                <option value="fail">Fail</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.right_difficulty" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="performed_at" value="Date Performed" />
                                <TextInput
                                    id="performed_at"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.performed_at"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.performed_at" />
                            </div>

                            <div>
                                <InputLabel for="notes" value="Notes (optional)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    rows="3"
                                    placeholder="Any additional notes about this workout..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.notes" />
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('workouts.show', workout.id)"
                                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    Update Workout
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>