<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface WorkoutType {
    id: number;
    name: string;
    description: string;
    muscle_group: string;
    equipment_needed: string;
}

interface Props {
    workoutTypes: WorkoutType[];
}

const props = defineProps<Props>();

const form = useForm({
    workout_type_id: '',
    sets: '',
    reps: '',
    weight: '',
    duration_minutes: '',
    distance: '',
    notes: '',
    performed_at: new Date().toISOString().split('T')[0],
});

const submit = () => {
    form.post(route('workouts.store'));
};
</script>

<template>
    <Head title="Log Workout" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Log New Workout
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            </div>

                            <div>
                                <InputLabel for="distance" value="Distance (km)" />
                                <TextInput
                                    id="distance"
                                    type="number"
                                    step="0.1"
                                    class="mt-1 block w-full"
                                    v-model="form.distance"
                                    placeholder="e.g., 5.0"
                                />
                                <InputError class="mt-2" :message="form.errors.distance" />
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

                            <div class="flex items-center justify-end">
                                <PrimaryButton class="ms-4" :disabled="form.processing">
                                    Log Workout
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>