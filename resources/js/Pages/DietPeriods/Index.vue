<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

interface DietPeriod {
    id: number;
    name: string;
    phase_type: string;
    start_date: string;
    end_date?: string;
    notes?: string;
    target_calories?: number;
    target_protein?: number;
    target_carbs?: number;
    target_fat?: number;
    starting_weight?: number;
    target_weight?: number;
}

interface Props {
    periods: DietPeriod[];
    currentPeriod?: DietPeriod;
}

const props = defineProps<Props>();

const showCreateForm = ref(false);
const showEditForm = ref(false);
const showEndCurrentForm = ref(false);
const editingPeriod = ref<DietPeriod | null>(null);
const confirmingPeriodDeletion = ref(false);
const periodToDelete = ref<DietPeriod | null>(null);

const createForm = useForm({
    name: '',
    phase_type: 'maintenance',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    notes: '',
    target_calories: '',
    target_protein: '',
    target_carbs: '',
    target_fat: '',
    starting_weight: '',
    target_weight: '',
});

const editForm = useForm({
    name: '',
    phase_type: 'maintenance',
    start_date: '',
    end_date: '',
    notes: '',
    target_calories: '',
    target_protein: '',
    target_carbs: '',
    target_fat: '',
    starting_weight: '',
    target_weight: '',
});

const endCurrentForm = useForm({
    end_date: new Date().toISOString().split('T')[0],
});

function submitCreate() {
    createForm.post(route('diet-periods.store'), {
        onSuccess: () => {
            showCreateForm.value = false;
            createForm.reset();
        }
    });
}

function editPeriod(period: DietPeriod) {
    editingPeriod.value = period;
    editForm.name = period.name;
    editForm.phase_type = period.phase_type;
    editForm.start_date = period.start_date;
    editForm.end_date = period.end_date || '';
    editForm.notes = period.notes || '';
    editForm.target_calories = period.target_calories?.toString() || '';
    editForm.target_protein = period.target_protein?.toString() || '';
    editForm.target_carbs = period.target_carbs?.toString() || '';
    editForm.target_fat = period.target_fat?.toString() || '';
    editForm.starting_weight = period.starting_weight?.toString() || '';
    editForm.target_weight = period.target_weight?.toString() || '';
    showEditForm.value = true;
}

function submitEdit() {
    if (!editingPeriod.value) return;
    
    editForm.put(route('diet-periods.update', editingPeriod.value.id), {
        onSuccess: () => {
            showEditForm.value = false;
            editingPeriod.value = null;
            editForm.reset();
        }
    });
}

function confirmDeletePeriod(period: DietPeriod) {
    periodToDelete.value = period;
    confirmingPeriodDeletion.value = true;
}

function deletePeriod() {
    if (!periodToDelete.value) return;
    
    router.delete(route('diet-periods.destroy', periodToDelete.value.id), {
        onSuccess: () => {
            confirmingPeriodDeletion.value = false;
            periodToDelete.value = null;
        }
    });
}

function submitEndCurrent() {
    endCurrentForm.post(route('diet-periods.end-current'), {
        onSuccess: () => {
            showEndCurrentForm.value = false;
            endCurrentForm.reset();
        }
    });
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function getPhaseColor(phase: string) {
    const colors = {
        cut: 'text-red-600 bg-red-50 dark:bg-red-900/20',
        bulk: 'text-green-600 bg-green-50 dark:bg-green-900/20',
        maintenance: 'text-blue-600 bg-blue-50 dark:bg-blue-900/20'
    };
    return colors[phase as keyof typeof colors] || colors.maintenance;
}

function getDuration(period: DietPeriod) {
    const start = new Date(period.start_date);
    const end = period.end_date ? new Date(period.end_date) : new Date();
    const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
    return `${days} day${days !== 1 ? 's' : ''}`;
}
</script>

<template>
    <Head title="Diet Periods" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Diet Periods
                </h2>
                <PrimaryButton @click="showCreateForm = true">
                    Create New Period
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Current Period Card -->
                <div v-if="currentPeriod" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ currentPeriod.name }}
                                </h3>
                                <span :class="`px-2 py-1 text-xs font-medium rounded-full capitalize ${getPhaseColor(currentPeriod.phase_type)}`">
                                    {{ currentPeriod.phase_type }}
                                </span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    Active
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                Started {{ formatDate(currentPeriod.start_date) }} • {{ getDuration(currentPeriod) }}
                            </div>
                            <div v-if="currentPeriod.notes" class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                {{ currentPeriod.notes }}
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                                <div v-if="currentPeriod.target_calories" class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Target Calories</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ Math.round(currentPeriod.target_calories) }}</div>
                                </div>
                                <div v-if="currentPeriod.target_protein" class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Target Protein</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ Math.round(currentPeriod.target_protein) }}g</div>
                                </div>
                                <div v-if="currentPeriod.starting_weight" class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Starting Weight</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ currentPeriod.starting_weight }} lbs</div>
                                </div>
                                <div v-if="currentPeriod.target_weight" class="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Target Weight</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ currentPeriod.target_weight }} lbs</div>
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <SecondaryButton @click="editPeriod(currentPeriod)">
                                Edit
                            </SecondaryButton>
                            <DangerButton @click="showEndCurrentForm = true">
                                End Period
                            </DangerButton>
                        </div>
                    </div>
                </div>

                <!-- No Current Period -->
                <div v-else class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                No Active Diet Period
                            </h3>
                            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                Create a new period to start tracking your diet phase with specific targets.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Historical Periods -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Period History</h3>
                    </div>
                    
                    <div v-if="periods.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
                        No diet periods created yet
                    </div>
                    
                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="period in periods" :key="period.id" class="p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h4 class="text-md font-semibold text-gray-900 dark:text-white">
                                            {{ period.name }}
                                        </h4>
                                        <span :class="`px-2 py-1 text-xs font-medium rounded-full capitalize ${getPhaseColor(period.phase_type)}`">
                                            {{ period.phase_type }}
                                        </span>
                                        <span v-if="!period.end_date" class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                            Active
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                        {{ formatDate(period.start_date) }}
                                        <span v-if="period.end_date">
                                            - {{ formatDate(period.end_date) }}
                                        </span>
                                        <span v-else>
                                            - Present
                                        </span>
                                        • {{ getDuration(period) }}
                                    </div>
                                    <div v-if="period.notes" class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                                        {{ period.notes }}
                                    </div>
                                    
                                    <!-- Targets in a compact grid -->
                                    <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-xs">
                                        <div v-if="period.target_calories" class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-center">
                                            <div class="text-gray-500 dark:text-gray-400">Cal</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ Math.round(period.target_calories) }}</div>
                                        </div>
                                        <div v-if="period.target_protein" class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-center">
                                            <div class="text-gray-500 dark:text-gray-400">Protein</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ Math.round(period.target_protein) }}g</div>
                                        </div>
                                        <div v-if="period.target_carbs" class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-center">
                                            <div class="text-gray-500 dark:text-gray-400">Carbs</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ Math.round(period.target_carbs) }}g</div>
                                        </div>
                                        <div v-if="period.target_fat" class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-center">
                                            <div class="text-gray-500 dark:text-gray-400">Fat</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ Math.round(period.target_fat) }}g</div>
                                        </div>
                                        <div v-if="period.starting_weight" class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-center">
                                            <div class="text-gray-500 dark:text-gray-400">Start</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ period.starting_weight }}lbs</div>
                                        </div>
                                        <div v-if="period.target_weight" class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-center">
                                            <div class="text-gray-500 dark:text-gray-400">Target</div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ period.target_weight }}lbs</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2 ml-4">
                                    <SecondaryButton @click="editPeriod(period)">
                                        Edit
                                    </SecondaryButton>
                                    <DangerButton @click="confirmDeletePeriod(period)">
                                        Delete
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Period Modal -->
        <Modal :show="showCreateForm" @close="showCreateForm = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Create New Diet Period
                </h2>
                
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="create_name" value="Period Name" />
                            <TextInput
                                id="create_name"
                                v-model="createForm.name"
                                class="w-full"
                                placeholder="e.g., Summer Cut 2025"
                                required
                            />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="create_phase_type" value="Phase Type" />
                            <select
                                id="create_phase_type"
                                v-model="createForm.phase_type"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="cut">Cut</option>
                                <option value="bulk">Bulk</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                            <InputError :message="createForm.errors.phase_type" />
                        </div>
                        <div>
                            <InputLabel for="create_start_date" value="Start Date" />
                            <TextInput
                                id="create_start_date"
                                type="date"
                                v-model="createForm.start_date"
                                class="w-full"
                                required
                            />
                            <InputError :message="createForm.errors.start_date" />
                        </div>
                        <div>
                            <InputLabel for="create_end_date" value="End Date (Optional)" />
                            <TextInput
                                id="create_end_date"
                                type="date"
                                v-model="createForm.end_date"
                                class="w-full"
                            />
                            <InputError :message="createForm.errors.end_date" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="create_notes" value="Notes" />
                        <textarea
                            id="create_notes"
                            v-model="createForm.notes"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            rows="3"
                            placeholder="Goals, motivation, etc."
                        ></textarea>
                        <InputError :message="createForm.errors.notes" />
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel for="create_target_calories" value="Target Calories" />
                            <TextInput
                                id="create_target_calories"
                                type="number"
                                v-model="createForm.target_calories"
                                class="w-full"
                            />
                            <InputError :message="createForm.errors.target_calories" />
                        </div>
                        <div>
                            <InputLabel for="create_target_protein" value="Target Protein (g)" />
                            <TextInput
                                id="create_target_protein"
                                type="number"
                                step="0.1"
                                v-model="createForm.target_protein"
                                class="w-full"
                            />
                            <InputError :message="createForm.errors.target_protein" />
                        </div>
                        <div>
                            <InputLabel for="create_starting_weight" value="Starting Weight (lbs)" />
                            <TextInput
                                id="create_starting_weight"
                                type="number"
                                step="0.1"
                                v-model="createForm.starting_weight"
                                class="w-full"
                            />
                            <InputError :message="createForm.errors.starting_weight" />
                        </div>
                        <div>
                            <InputLabel for="create_target_weight" value="Target Weight (lbs)" />
                            <TextInput
                                id="create_target_weight"
                                type="number"
                                step="0.1"
                                v-model="createForm.target_weight"
                                class="w-full"
                            />
                            <InputError :message="createForm.errors.target_weight" />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <SecondaryButton @click="showCreateForm = false">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="createForm.processing">
                            Create Period
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Period Modal -->
        <Modal :show="showEditForm" @close="showEditForm = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Edit Diet Period
                </h2>
                
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="edit_name" value="Period Name" />
                            <TextInput
                                id="edit_name"
                                v-model="editForm.name"
                                class="w-full"
                                required
                            />
                            <InputError :message="editForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="edit_phase_type" value="Phase Type" />
                            <select
                                id="edit_phase_type"
                                v-model="editForm.phase_type"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="cut">Cut</option>
                                <option value="bulk">Bulk</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                            <InputError :message="editForm.errors.phase_type" />
                        </div>
                        <div>
                            <InputLabel for="edit_start_date" value="Start Date" />
                            <TextInput
                                id="edit_start_date"
                                type="date"
                                v-model="editForm.start_date"
                                class="w-full"
                                required
                            />
                            <InputError :message="editForm.errors.start_date" />
                        </div>
                        <div>
                            <InputLabel for="edit_end_date" value="End Date (Optional)" />
                            <TextInput
                                id="edit_end_date"
                                type="date"
                                v-model="editForm.end_date"
                                class="w-full"
                            />
                            <InputError :message="editForm.errors.end_date" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="edit_notes" value="Notes" />
                        <textarea
                            id="edit_notes"
                            v-model="editForm.notes"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            rows="3"
                        ></textarea>
                        <InputError :message="editForm.errors.notes" />
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel for="edit_target_calories" value="Target Calories" />
                            <TextInput
                                id="edit_target_calories"
                                type="number"
                                v-model="editForm.target_calories"
                                class="w-full"
                            />
                            <InputError :message="editForm.errors.target_calories" />
                        </div>
                        <div>
                            <InputLabel for="edit_target_protein" value="Target Protein (g)" />
                            <TextInput
                                id="edit_target_protein"
                                type="number"
                                step="0.1"
                                v-model="editForm.target_protein"
                                class="w-full"
                            />
                            <InputError :message="editForm.errors.target_protein" />
                        </div>
                        <div>
                            <InputLabel for="edit_starting_weight" value="Starting Weight (lbs)" />
                            <TextInput
                                id="edit_starting_weight"
                                type="number"
                                step="0.1"
                                v-model="editForm.starting_weight"
                                class="w-full"
                            />
                            <InputError :message="editForm.errors.starting_weight" />
                        </div>
                        <div>
                            <InputLabel for="edit_target_weight" value="Target Weight (lbs)" />
                            <TextInput
                                id="edit_target_weight"
                                type="number"
                                step="0.1"
                                v-model="editForm.target_weight"
                                class="w-full"
                            />
                            <InputError :message="editForm.errors.target_weight" />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <SecondaryButton @click="showEditForm = false">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing">
                            Update Period
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- End Current Period Modal -->
        <Modal :show="showEndCurrentForm" @close="showEndCurrentForm = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    End Current Period
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    This will end your current diet period. You can create a new one afterwards.
                </p>
                
                <form @submit.prevent="submitEndCurrent">
                    <div class="mb-4">
                        <InputLabel for="end_date" value="End Date" />
                        <TextInput
                            id="end_date"
                            type="date"
                            v-model="endCurrentForm.end_date"
                            class="w-full"
                            required
                        />
                        <InputError :message="endCurrentForm.errors.end_date" />
                    </div>

                    <div class="flex justify-end space-x-2">
                        <SecondaryButton @click="showEndCurrentForm = false">
                            Cancel
                        </SecondaryButton>
                        <DangerButton type="submit" :disabled="endCurrentForm.processing">
                            End Period
                        </DangerButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="confirmingPeriodDeletion" @close="confirmingPeriodDeletion = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Delete Diet Period
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Are you sure you want to delete "{{ periodToDelete?.name }}"? This action cannot be undone.
                </p>

                <div class="flex justify-end space-x-2">
                    <SecondaryButton @click="confirmingPeriodDeletion = false">
                        Cancel
                    </SecondaryButton>
                    <DangerButton @click="deletePeriod">
                        Delete Period
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>