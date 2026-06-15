<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface RecipeSource {
    id: number;
    title: string;
    url: string | null;
    type: string | null;
}

interface FoodType {
    id: number;
    name: string;
}

interface Recipe {
    id: number;
    name: string;
    description: string | null;
    servings: string | null;
    food_type: FoodType | null;
    sources: RecipeSource[];
}

interface Props {
    recipes: Recipe[];
}

defineProps<Props>();

const showDeleteModal = ref(false);
const selectedRecipe = ref<Recipe | null>(null);

function confirmDelete(recipe: Recipe) {
    selectedRecipe.value = recipe;
    showDeleteModal.value = true;
}

function deleteRecipe() {
    if (!selectedRecipe.value) return;

    router.delete(`/recipes/${selectedRecipe.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedRecipe.value = null;
        },
    });
}

function closeModal() {
    showDeleteModal.value = false;
    selectedRecipe.value = null;
}
</script>

<template>
    <Head title="Recipes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Recipes
                </h2>
                <PrimaryButton @click="router.visit('/recipes/create')">
                    Add Recipe
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="recipes.length === 0" class="rounded-xl border border-gray-100 bg-white p-6 text-center text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    No recipes yet. Add one to get started.
                </div>

                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="recipe in recipes"
                        :key="recipe.id"
                        class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <Link :href="`/recipes/${recipe.id}`" class="hover:underline">
                                    {{ recipe.name }}
                                </Link>
                            </h3>
                        </div>

                        <p v-if="recipe.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ recipe.description }}
                        </p>

                        <div v-if="recipe.food_type" class="mt-2 text-sm">
                            <Link
                                :href="`/food-types/${recipe.food_type.id}`"
                                class="text-brand-600 hover:underline dark:text-brand-400"
                            >
                                {{ recipe.food_type.name }}
                            </Link>
                        </div>

                        <div v-if="recipe.sources.length > 0" class="mt-3 space-y-1">
                            <div class="text-xs font-medium uppercase text-gray-400 dark:text-gray-500">
                                Sources
                            </div>
                            <ul class="space-y-1 text-sm">
                                <li v-for="source in recipe.sources" :key="source.id">
                                    <a
                                        v-if="source.url"
                                        :href="source.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-brand-600 hover:underline dark:text-brand-400"
                                    >
                                        {{ source.title }}
                                    </a>
                                    <span v-else class="text-gray-700 dark:text-gray-300">
                                        {{ source.title }}
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <Link :href="`/recipes/${recipe.id}`">
                                <SecondaryButton>View</SecondaryButton>
                            </Link>
                            <Link :href="`/recipes/${recipe.id}/edit`">
                                <SecondaryButton>Edit</SecondaryButton>
                            </Link>
                            <DangerButton @click="confirmDelete(recipe)">
                                Delete
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Delete Recipe
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Are you sure you want to delete "{{ selectedRecipe?.name }}"? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>
                    <DangerButton @click="deleteRecipe">
                        Delete
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
