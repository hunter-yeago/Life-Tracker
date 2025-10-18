<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface WorkoutType {
    id: number;
    name: string;
    description: string | null;
    muscle_group: string;
    equipment_needed: string | null;
    sides: string;
}

interface Props {
    workoutType: WorkoutType;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.workoutType.name,
    description: props.workoutType.description || '',
    muscle_group: props.workoutType.muscle_group,
    equipment_needed: props.workoutType.equipment_needed || '',
    sides: props.workoutType.sides,
});

const submit = () => {
    form.put(route('workout-types.update', props.workoutType.id));
};
</script>

<template>
    <Head title="Edit Workout Type" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Workout Type
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('workout-types.show', workoutType.id)"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm"
                    >
                        Cancel
                    </Link>
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
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Workout Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    placeholder="e.g., Bench Press"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="description" value="Description (optional)" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    rows="3"
                                    placeholder="Describe this workout and how to perform it..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <div>
                                <InputLabel for="muscle_group" value="Muscle Group" />
                                <select
                                    id="muscle_group"
                                    v-model="form.muscle_group"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="">Select muscle group</option>
                                    <option value="Chest">Chest</option>
                                    <option value="Back">Back</option>
                                    <option value="Shoulders">Shoulders</option>
                                    <option value="Arms">Arms</option>
                                    <option value="Legs">Legs</option>
                                    <option value="Core">Core</option>
                                    <option value="Cardio">Cardio</option>
                                    <option value="Full Body">Full Body</option>
                                    <option value="Other">Other</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.muscle_group" />
                            </div>

                            <div>
                                <InputLabel for="equipment_needed" value="Equipment Needed (optional)" />
                                <TextInput
                                    id="equipment_needed"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.equipment_needed"
                                    placeholder="e.g., Barbell, Dumbbells, None"
                                />
                                <InputError class="mt-2" :message="form.errors.equipment_needed" />
                            </div>

                            <div>
                                <InputLabel for="sides" value="Side Support" />
                                <select
                                    id="sides"
                                    v-model="form.sides"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="none">No left/right sides</option>
                                    <option value="both">Can track both sides together</option>
                                    <option value="separate">Track left/right sides separately</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.sides" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Choose "separate" for exercises like single-arm rows where you want to track each side independently
                                </p>
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('workout-types.index')"
                                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    Update Workout Type
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>