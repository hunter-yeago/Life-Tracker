<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface WorkoutType {
    id: number;
    name: string;
    description: string;
    muscle_group: string;
    equipment_needed: string;
    sides: string;
}

interface Props {
    workoutTypes: WorkoutType[];
    preselected?: {
        workout_type_id?: string;
        performed_at?: string;
    };
}

const props = defineProps<Props>();

interface WorkoutSetData {
    set_number: number;
    reps: string;
    weight: string;
    duration_seconds: string;
    difficulty: string;
    completed: boolean;
    notes: string;
}

const form = useForm({
    workout_type_id: props.preselected?.workout_type_id || '',
    notes: '',
    performed_at: props.preselected?.performed_at || new Date().toISOString().split('T')[0],
    sets: [
        {
            set_number: 1,
            reps: '',
            weight: '',
            duration_seconds: '',
            difficulty: '',
            completed: true,
            notes: ''
        }
    ] as WorkoutSetData[],
});

const selectedWorkoutType = computed(() => {
    return props.workoutTypes.find(type => type.id.toString() === form.workout_type_id);
});

const supportsSides = computed(() => {
    return selectedWorkoutType.value?.sides === 'both' || selectedWorkoutType.value?.sides === 'separate';
});

const addSet = () => {
    const nextSetNumber = Math.max(...form.sets.map(s => s.set_number)) + 1;
    form.sets.push({
        set_number: nextSetNumber,
        reps: '',
        weight: '',
        duration_seconds: '',
        difficulty: '',
        completed: true,
        notes: ''
    });
};

const removeSet = (index: number) => {
    if (form.sets.length > 1) {
        form.sets.splice(index, 1);
        // Renumber the remaining sets
        form.sets.forEach((set, i) => {
            set.set_number = i + 1;
        });
    }
};

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

                            <!-- Sets Section -->
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sets</h3>
                                    <button
                                        type="button"
                                        @click="addSet"
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                                    >
                                        Add Set
                                    </button>
                                </div>

                                <div
                                    v-for="(set, index) in form.sets"
                                    :key="set.set_number"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                                >
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="font-medium text-gray-900 dark:text-white">Set {{ set.set_number }}</h4>
                                        <button
                                            v-if="form.sets.length > 1"
                                            type="button"
                                            @click="removeSet(index)"
                                            class="text-red-600 hover:text-red-800 text-sm"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <div>
                                            <InputLabel :for="`reps_${index}`" value="Reps" />
                                            <TextInput
                                                :id="`reps_${index}`"
                                                type="number"
                                                class="mt-1 block w-full"
                                                v-model="set.reps"
                                                placeholder="10"
                                            />
                                            <InputError class="mt-2" :message="form.errors[`sets.${index}.reps`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`weight_${index}`" value="Weight (lbs)" />
                                            <TextInput
                                                :id="`weight_${index}`"
                                                type="number"
                                                step="0.1"
                                                class="mt-1 block w-full"
                                                v-model="set.weight"
                                                placeholder="60"
                                            />
                                            <InputError class="mt-2" :message="form.errors[`sets.${index}.weight`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`duration_${index}`" value="Time (sec)" />
                                            <TextInput
                                                :id="`duration_${index}`"
                                                type="number"
                                                class="mt-1 block w-full"
                                                v-model="set.duration_seconds"
                                                placeholder="60"
                                            />
                                            <InputError class="mt-2" :message="form.errors[`sets.${index}.duration_seconds`]" />
                                        </div>

                                        <div>
                                            <InputLabel :for="`difficulty_${index}`" value="Difficulty" />
                                            <select
                                                :id="`difficulty_${index}`"
                                                v-model="set.difficulty"
                                                class="mt-1 block w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            >
                                                <option value="">-</option>
                                                <option value="easy">Easy</option>
                                                <option value="hard">Hard</option>
                                                <option value="really_hard">Really Hard</option>
                                                <option value="almost_fail">Almost Fail</option>
                                                <option value="fail">Fail</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors[`sets.${index}.difficulty`]" />
                                        </div>
                                    </div>

                                    <div v-if="set.notes || form.sets.length > 1" class="mt-3">
                                        <InputLabel :for="`notes_${index}`" value="Set Notes (optional)" />
                                        <textarea
                                            :id="`notes_${index}`"
                                            v-model="set.notes"
                                            class="mt-1 block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            rows="2"
                                            placeholder="Notes for this set..."
                                        ></textarea>
                                        <InputError class="mt-2" :message="form.errors[`sets.${index}.notes`]" />
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
                                <InputLabel for="notes" value="Workout Notes (optional)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    rows="3"
                                    placeholder="Overall notes about this workout session..."
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