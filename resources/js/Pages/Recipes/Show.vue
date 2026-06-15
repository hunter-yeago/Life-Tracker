<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    ingredients: string | null;
    instructions: string | null;
    food_type: FoodType | null;
    sources: RecipeSource[];
}

interface Props {
    recipe: Recipe;
}

const props = defineProps<Props>();

const ingredientLines = computed(() =>
    (props.recipe.ingredients || '')
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0)
);

const instructionLines = computed(() =>
    (props.recipe.instructions || '')
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0)
);
</script>

<template>
    <Head :title="recipe.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ recipe.name }}
                </h2>
                <div class="flex gap-2">
                    <Link :href="`/recipes/${recipe.id}/edit`">
                        <SecondaryButton>Edit</SecondaryButton>
                    </Link>
                    <Link href="/recipes">
                        <SecondaryButton>Back to Recipes</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <p v-if="recipe.description" class="text-gray-700 dark:text-gray-300">
                        {{ recipe.description }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-4 text-sm">
                        <div v-if="recipe.servings">
                            <span class="text-gray-500 dark:text-gray-400">Servings:</span>
                            <span class="ml-1 text-gray-900 dark:text-white">{{ recipe.servings }}</span>
                        </div>
                        <div v-if="recipe.food_type">
                            <span class="text-gray-500 dark:text-gray-400">Food Type:</span>
                            <Link
                                :href="`/food-types/${recipe.food_type.id}`"
                                class="ml-1 text-brand-600 hover:underline dark:text-brand-400"
                            >
                                {{ recipe.food_type.name }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="ingredientLines.length > 0" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Ingredients</h3>
                    <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                        <li v-for="(line, index) in ingredientLines" :key="index">{{ line }}</li>
                    </ul>
                </div>

                <div v-if="instructionLines.length > 0" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Instructions</h3>
                    <ol class="list-decimal list-inside space-y-1 text-gray-700 dark:text-gray-300">
                        <li v-for="(line, index) in instructionLines" :key="index">{{ line }}</li>
                    </ol>
                </div>

                <div v-if="recipe.sources.length > 0" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Sources</h3>
                    <ul class="space-y-2">
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
                            <span v-if="source.type" class="ml-2 text-xs uppercase text-gray-400 dark:text-gray-500">
                                {{ source.type }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
