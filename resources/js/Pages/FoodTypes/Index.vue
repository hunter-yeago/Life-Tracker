<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted } from 'vue';
import * as d3 from 'd3';
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
const usageData = ref<Record<string, number[]>>({});
const macroData = ref<Array<{date: string; protein: number; carbs: number; fat: number}>>([]);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showCreateModal = ref(false);
const showCharts = ref({
    protein: false,
    carbs: false,
    fat: false
});

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

const createForm = useForm({
    name: '',
    description: '',
    calories_per_serving: '',
    protein_per_serving: '',
    carbs_per_serving: '',
    fat_per_serving: '',
    is_one_time_item: false,
});

function searchFoodTypes() {
    router.get('/food-types', { search: searchQuery.value }, { preserveState: true });
}

async function viewFoodType(foodType: FoodType) {
    selectedFoodType.value = foodType;
    usageData.value = {}; // Reset usage data
    macroData.value = []; // Reset macro data
    
    try {
        const [usageResponse, macroResponse] = await Promise.all([
            fetch(`/api/food-types/${foodType.id}/usage`),
            fetch(`/api/food-types/${foodType.id}/macro-data`)
        ]);
        const [usage, macro] = await Promise.all([
            usageResponse.json(),
            macroResponse.json()
        ]);
        usageData.value = usage;
        macroData.value = macro;
    } catch (error) {
        console.error('Failed to fetch data:', error);
        usageData.value = {};
        macroData.value = [];
    }
}

function formatDecimalValue(value: number): string {
    // Convert to string and remove trailing .00
    const str = value.toString();
    if (str.endsWith('.00')) {
        return str.slice(0, -3);
    }
    return str;
}

function editFoodType(foodType: FoodType) {
    selectedFoodType.value = foodType;
    editForm.name = foodType.name;
    editForm.description = foodType.description || '';
    editForm.calories_per_serving = formatDecimalValue(foodType.calories_per_serving);
    editForm.protein_per_serving = formatDecimalValue(foodType.protein_per_serving);
    editForm.carbs_per_serving = formatDecimalValue(foodType.carbs_per_serving);
    editForm.fat_per_serving = formatDecimalValue(foodType.fat_per_serving);
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

function createFoodType() {
    createForm.post('/food-types', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function closeModal() {
    showEditModal.value = false;
    showDeleteModal.value = false;
    showCreateModal.value = false;
    selectedFoodType.value = null;
    editForm.reset();
    createForm.reset();
}

function toggleChart(macro: 'protein' | 'carbs' | 'fat') {
    showCharts.value[macro] = !showCharts.value[macro];
    if (showCharts.value[macro] && macroData.value.length > 0) {
        nextTick(() => renderChart(macro));
    }
}

function renderChart(macro: 'protein' | 'carbs' | 'fat') {
    const container = d3.select(`#${macro}-chart`);
    container.selectAll('*').remove(); // Clear previous chart
    
    if (macroData.value.length === 0) return;
    
    const margin = { top: 20, right: 30, bottom: 40, left: 40 };
    const width = 500 - margin.left - margin.right;
    const height = 200 - margin.top - margin.bottom;
    
    const svg = container
        .append('svg')
        .attr('width', width + margin.left + margin.right)
        .attr('height', height + margin.top + margin.bottom);
    
    const g = svg.append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);
    
    const parseDate = d3.timeParse('%Y-%m-%d');
    const data = macroData.value.map(d => ({
        date: parseDate(d.date)!,
        value: d[macro]
    }));
    
    const x = d3.scaleTime()
        .domain(d3.extent(data, d => d.date) as [Date, Date])
        .range([0, width]);
    
    const y = d3.scaleLinear()
        .domain([0, d3.max(data, d => d.value) || 0])
        .nice()
        .range([height, 0]);
    
    const line = d3.line<{date: Date; value: number}>()
        .x(d => x(d.date))
        .y(d => y(d.value))
        .curve(d3.curveMonotoneX);
    
    g.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(x).tickFormat(d3.timeFormat('%m/%d')));
    
    g.append('g')
        .call(d3.axisLeft(y));
    
    g.append('path')
        .datum(data)
        .attr('fill', 'none')
        .attr('stroke', macro === 'protein' ? '#3b82f6' : macro === 'carbs' ? '#10b981' : '#f59e0b')
        .attr('stroke-width', 2)
        .attr('d', line);
    
    g.selectAll('.dot')
        .data(data)
        .enter().append('circle')
        .attr('class', 'dot')
        .attr('cx', d => x(d.date))
        .attr('cy', d => y(d.value))
        .attr('r', 3)
        .attr('fill', macro === 'protein' ? '#3b82f6' : macro === 'carbs' ? '#10b981' : '#f59e0b');
}

function formatMonth(month: string): string {
    const [year, monthNum] = month.split('-');
    const date = new Date(parseInt(year), parseInt(monthNum) - 1);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
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
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Food Types Management
                </h2>
                <PrimaryButton @click="showCreateModal = true">
                    Create Food Type
                </PrimaryButton>
            </div>
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
                                        {{ Math.round(foodType.calories_per_serving || 0) }} cal/{{ foodType.serving_size || 'serving' }}
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
                                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Nutrition (per serving)</h4>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Calories</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round(selectedFoodType.calories_per_serving || 0) }}
                                                </div>
                                            </div>
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Protein</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round((selectedFoodType.protein_per_serving || 0) * 10) / 10 }}g
                                                </div>
                                            </div>
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Carbs</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round((selectedFoodType.carbs_per_serving || 0) * 10) / 10 }}g
                                                </div>
                                            </div>
                                            <div class="rounded bg-gray-50 p-2 dark:bg-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">Fat</div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ Math.round((selectedFoodType.fat_per_serving || 0) * 10) / 10 }}g
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chart Controls -->
                                <div v-if="selectedFoodType && macroData.length > 0" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Macro Charts (Last 30 Days)</h4>
                                    <div class="flex gap-2 mb-4">
                                        <SecondaryButton 
                                            @click="toggleChart('protein')"
                                            :class="{ 'bg-blue-100 dark:bg-blue-800': showCharts.protein }"
                                        >
                                            Protein
                                        </SecondaryButton>
                                        <SecondaryButton 
                                            @click="toggleChart('carbs')"
                                            :class="{ 'bg-green-100 dark:bg-green-800': showCharts.carbs }"
                                        >
                                            Carbs
                                        </SecondaryButton>
                                        <SecondaryButton 
                                            @click="toggleChart('fat')"
                                            :class="{ 'bg-yellow-100 dark:bg-yellow-800': showCharts.fat }"
                                        >
                                            Fat
                                        </SecondaryButton>
                                    </div>
                                    
                                    <!-- Charts -->
                                    <div class="space-y-4">
                                        <div v-if="showCharts.protein" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                            <h5 class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-2">Protein (g)</h5>
                                            <div id="protein-chart"></div>
                                        </div>
                                        <div v-if="showCharts.carbs" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                            <h5 class="text-sm font-medium text-green-600 dark:text-green-400 mb-2">Carbs (g)</h5>
                                            <div id="carbs-chart"></div>
                                        </div>
                                        <div v-if="showCharts.fat" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                            <h5 class="text-sm font-medium text-yellow-600 dark:text-yellow-400 mb-2">Fat (g)</h5>
                                            <div id="fat-chart"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Usage History -->
                                <div v-if="Object.keys(usageData).length > 0" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Usage History</h4>
                                    <div class="space-y-2 text-sm">
                                        <div 
                                            v-for="(days, month) in usageData" 
                                            :key="month"
                                            class="flex items-start"
                                        >
                                            <span class="text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">
                                                {{ formatMonth(month) }}:
                                            </span>
                                            <span class="text-gray-900 dark:text-white">
                                                {{ days.join(', ') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else-if="selectedFoodType" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Usage History</h4>
                                    <p v-if="Object.keys(usageData).length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                                        This food type hasn't been used yet.
                                    </p>
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
                        <div class="space-y-1 max-h-48 overflow-y-auto">
                            <div
                                v-for="foodType in filteredOneTimeFoodTypes"
                                :key="foodType.id"
                                class="cursor-pointer rounded p-2 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between"
                                :class="{ 'bg-blue-50 dark:bg-blue-900': selectedFoodType?.id === foodType.id }"
                                @click="viewFoodType(foodType)"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm truncate">
                                        {{ foodType.name }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 ml-2 flex-shrink-0">
                                    {{ Math.round(foodType.calories_per_serving || 0) }} cal
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="showCreateModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Create Food Type
                </h3>
                <form @submit.prevent="createFoodType" class="space-y-4">
                    <div>
                        <InputLabel for="create_name" value="Name" />
                        <TextInput
                            id="create_name"
                            v-model="createForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="createForm.errors.name" />
                    </div>
                    
                    <div>
                        <InputLabel for="create_description" value="Description" />
                        <textarea
                            id="create_description"
                            v-model="createForm.description"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            rows="3"
                        ></textarea>
                        <InputError class="mt-2" :message="createForm.errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="create_calories" value="Calories per serving" />
                            <TextInput
                                id="create_calories"
                                v-model="createForm.calories_per_serving"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.calories_per_serving" />
                        </div>
                        
                        <div>
                            <InputLabel for="create_protein" value="Protein per serving (g)" />
                            <TextInput
                                id="create_protein"
                                v-model="createForm.protein_per_serving"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.protein_per_serving" />
                        </div>
                        
                        <div>
                            <InputLabel for="create_carbs" value="Carbs per serving (g)" />
                            <TextInput
                                id="create_carbs"
                                v-model="createForm.carbs_per_serving"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.carbs_per_serving" />
                        </div>
                        
                        <div>
                            <InputLabel for="create_fat" value="Fat per serving (g)" />
                            <TextInput
                                id="create_fat"
                                v-model="createForm.fat_per_serving"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.fat_per_serving" />
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input
                            id="create_is_one_time"
                            v-model="createForm.is_one_time_item"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                        />
                        <label for="create_is_one_time" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            One-time item (cannot be reused)
                        </label>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 -mt-2">
                        Check this for specific meals like "Subway Turkey Sandwich" that you won't reuse
                    </div>

                    <div class="flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="closeModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="createForm.processing">
                            Create Food Type
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Edit Food Type
                </h3>

                <form @submit.prevent="updateFoodType" @keydown.enter.prevent="updateFoodType" class="space-y-4">
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
                            <InputLabel for="calories" value="Calories (per serving)" />
                            <TextInput
                                id="calories"
                                v-model="editForm.calories_per_serving"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.calories_per_serving" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="protein" value="Protein (per serving)" />
                            <TextInput
                                id="protein"
                                v-model="editForm.protein_per_serving"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.protein_per_serving" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="carbs" value="Carbs (per serving)" />
                            <TextInput
                                id="carbs"
                                v-model="editForm.carbs_per_serving"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.carbs_per_serving" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="fat" value="Fat (per serving)" />
                            <TextInput
                                id="fat"
                                v-model="editForm.fat_per_serving"
                                type="number"
                                step="0.1"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="editForm.errors.fat_per_serving" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing">
                            Save Changes
                        </PrimaryButton>
                    </div>
                </form>
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