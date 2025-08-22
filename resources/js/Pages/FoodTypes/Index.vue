<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

interface FoodType {
    id: number;
    name: string;
    description: string;
    serving_size?: string;
    serving_weight_grams?: number;
    calories_per_serving: number;
    protein_per_serving: number;
    carbs_per_serving: number;
    fat_per_serving: number;
    category: string;
    is_one_time_item: boolean;
}

interface Props {
    regularFoodTypes: {
        data: FoodType[];
        links: any[];
        meta: any;
    };
    oneTimeFoodTypes: FoodType[];
    search: string;
}

const props = defineProps<Props>();

const searchQuery = ref(props.search || '');
const selectedFoodType = ref<FoodType | null>(null);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

const editForm = useForm({
    name: '',
    description: '',
    serving_size: '',
    serving_weight_grams: '0',
    calories_per_serving: '0',
    protein_per_serving: '0',
    carbs_per_serving: '0',
    fat_per_serving: '0',
    category: '',
    is_one_time_item: false,
});

function searchFoodTypes() {
    router.get('/food-types', { search: searchQuery.value }, { preserveState: true });
}

function viewFoodType(foodType: FoodType) {
    selectedFoodType.value = foodType;
}

function editFoodType(foodType: FoodType) {
    selectedFoodType.value = foodType;
    editForm.name = foodType.name;
    editForm.description = foodType.description || '';
    editForm.calories_per_100g = foodType.calories_per_100g.toString();
    editForm.protein_per_100g = foodType.protein_per_100g.toString();
    editForm.carbs_per_100g = foodType.carbs_per_100g.toString();
    editForm.fat_per_100g = foodType.fat_per_100g.toString();
    editForm.category = foodType.category || '';
    showEditModal.value = true;
}

function updateFoodType() {
    if (!selectedFoodType.value) return;
    
    editForm.patch(`/food-types/${selectedFoodType.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            selectedFoodType.value = null;
            editForm.reset();
        },
    });
}

function confirmDelete(foodType: FoodType) {
    selectedFoodType.value = foodType;
    showDeleteModal.value = true;
}

function deleteFoodType() {
    if (!selectedFoodType.value) return;
    
    router.delete(`/food-types/${selectedFoodType.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedFoodType.value = null;
        },
    });
}

function closeModal() {
    showEditModal.value = false;
    showDeleteModal.value = false;
    selectedFoodType.value = null;
    editForm.reset();
}

const filteredRegularFoodTypes = computed(() => {
    if (!searchQuery.value) return props.regularFoodTypes.data;
    
    const query = searchQuery.value.toLowerCase();
    return props.regularFoodTypes.data.filter(foodType => 
        foodType.name.toLowerCase().includes(query) ||
        (foodType.description && foodType.description.toLowerCase().includes(query))
    );
});

const filteredOneTimeFoodTypes = computed(() => {
    if (!searchQuery.value) return props.oneTimeFoodTypes;
    
    const query = searchQuery.value.toLowerCase();
    return props.oneTimeFoodTypes.filter(foodType => 
        foodType.name.toLowerCase().includes(query) ||
        (foodType.description && foodType.description.toLowerCase().includes(query))
    );
});
</script>

<template>
    <Head title="Food Types" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Food Types Management
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search Bar -->
                <div class="mb-6 flex gap-4">
                    <div class="flex-1">
                        <TextInput
                            v-model="searchQuery"
                            placeholder="Search food types..."
                            class="w-full"
                            @keyup.enter="searchFoodTypes"
                        />
                    </div>
                    <PrimaryButton @click="searchFoodTypes">
                        Search
                    </PrimaryButton>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Search Results -->
                    <div class="lg:col-span-1">
                        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                                Regular Food Types ({{ filteredRegularFoodTypes.length }})
                            </h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <div
                                    v-for="foodType in filteredRegularFoodTypes"
                                    :key="foodType.id"
                                    class="cursor-pointer rounded-lg border p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                    :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900': selectedFoodType?.id === foodType.id }"
                                    @click="viewFoodType(foodType)"
                                >
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ foodType.name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ Math.round(foodType.calories_per_serving) }} cal/{{ foodType.serving_size || 'serving' }}
                                    </div>
                                </div>
                                <div v-if="filteredRegularFoodTypes.length === 0" class="text-center text-gray-500 py-4">
                                    No food types found
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Food Type Details -->
                    <div class="lg:col-span-2">
                        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <div v-if="selectedFoodType">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ selectedFoodType.name }}
                                    </h3>
                                    <div class="flex gap-2">
                                        <SecondaryButton @click="editFoodType(selectedFoodType)">
                                            Edit
                                        </SecondaryButton>
                                        <DangerButton @click="confirmDelete(selectedFoodType)">
                                            Delete
                                        </DangerButton>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Basic Information</h4>
                                        <div class="space-y-2 text-sm">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Description:</span>
                                                <span class="ml-2 text-gray-900 dark:text-white">
                                                    {{ selectedFoodType.description || 'No description' }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Category:</span>
                                                <span class="ml-2 text-gray-900 dark:text-white">
                                                    {{ selectedFoodType.category || 'No category' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Nutrition (per 100g)</h4>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Calories</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round(selectedFoodType.calories_per_100g) }}
                                                </div>
                                            </div>
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Protein</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round(selectedFoodType.protein_per_100g * 10) / 10 }}g
                                                </div>
                                            </div>
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Carbs</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round(selectedFoodType.carbs_per_100g * 10) / 10 }}g
                                                </div>
                                            </div>
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Fat</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round(selectedFoodType.fat_per_100g * 10) / 10 }}g
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-gray-500 py-8">
                                Select a food type to view details
                            </div>
                        </div>
                    </div>
                </div>

                <!-- One-Time Items Section -->
                <div v-if="filteredOneTimeFoodTypes.length > 0" class="mt-8">
                    <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                            One-Time Items ({{ filteredOneTimeFoodTypes.length }})
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            These are specific meal items that were logged once and are not available for reuse in new food entries.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="foodType in filteredOneTimeFoodTypes"
                                :key="foodType.id"
                                class="cursor-pointer rounded-lg border p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900': selectedFoodType?.id === foodType.id }"
                                @click="viewFoodType(foodType)"
                            >
                                <div class="font-medium text-gray-900 dark:text-white mb-1">
                                    {{ foodType.name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                    {{ Math.round(foodType.calories_per_serving) }} cal/{{ foodType.serving_size || 'serving' }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ foodType.category || 'No category' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Edit Food Type
                </h3>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="editForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="editForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="editForm.description"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                            rows="3"
                        ></textarea>
                        <InputError :message="editForm.errors.description" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="category" value="Category" />
                        <TextInput
                            id="category"
                            v-model="editForm.category"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="editForm.errors.category" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="calories" value="Calories (per 100g)" />
                            <TextInput
                                id="calories"
                                v-model="editForm.calories_per_100g"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.calories_per_100g" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="protein" value="Protein (per 100g)" />
                            <TextInput
                                id="protein"
                                v-model="editForm.protein_per_100g"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.protein_per_100g" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="carbs" value="Carbs (per 100g)" />
                            <TextInput
                                id="carbs"
                                v-model="editForm.carbs_per_100g"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.carbs_per_100g" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="fat" value="Fat (per 100g)" />
                            <TextInput
                                id="fat"
                                v-model="editForm.fat_per_100g"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.fat_per_100g" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton @click="updateFoodType" :disabled="editForm.processing">
                        Save Changes
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Delete Food Type
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Are you sure you want to delete "{{ selectedFoodType?.name }}"? This action cannot be undone and will affect all food entries using this food type.
                </p>
                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>
                    <DangerButton @click="deleteFoodType">
                        Delete
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>