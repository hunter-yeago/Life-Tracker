<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface FoodType {
    id: number;
    name: string;
}

interface RecipeSource {
    id: number;
    title: string;
    url: string | null;
    type: string | null;
}

interface Recipe {
    id: number;
    name: string;
    description: string | null;
    food_type_id: number | null;
    servings: string | null;
    ingredients: string | null;
    instructions: string | null;
    sources: RecipeSource[];
}

interface Props {
    recipe: Recipe;
    foodTypes: FoodType[];
}

const props = defineProps<Props>();

interface RecipeSourceData {
    title: string;
    url: string;
    type: string;
}

const form = useForm({
    name: props.recipe.name,
    description: props.recipe.description || '',
    food_type_id: props.recipe.food_type_id ?? '',
    servings: props.recipe.servings || '',
    ingredients: props.recipe.ingredients || '',
    instructions: props.recipe.instructions || '',
    sources: props.recipe.sources.map((source) => ({
        title: source.title,
        url: source.url || '',
        type: source.type || '',
    })) as RecipeSourceData[],
});

function addSource() {
    form.sources.push({ title: '', url: '', type: '' });
}

function removeSource(index: number) {
    form.sources.splice(index, 1);
}

function submit() {
    form.put(`/recipes/${props.recipe.id}`);
}
</script>

<template>
    <Head title="Edit Recipe" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Edit Recipe
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-600 dark:focus:ring-brand-600"
                                rows="2"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="food_type_id" value="Linked Food Type" />
                                <select
                                    id="food_type_id"
                                    v-model="form.food_type_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 dark:focus:border-brand-600 focus:ring-brand-500 dark:focus:ring-brand-600 rounded-md shadow-sm"
                                >
                                    <option value="">None</option>
                                    <option
                                        v-for="foodType in foodTypes"
                                        :key="foodType.id"
                                        :value="foodType.id"
                                    >
                                        {{ foodType.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.food_type_id" />
                            </div>

                            <div>
                                <InputLabel for="servings" value="Servings" />
                                <TextInput
                                    id="servings"
                                    v-model="form.servings"
                                    type="text"
                                    placeholder="e.g. 1 serving"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.servings" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="ingredients" value="Ingredients (one per line)" />
                            <textarea
                                id="ingredients"
                                v-model="form.ingredients"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-600 dark:focus:ring-brand-600"
                                rows="6"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.ingredients" />
                        </div>

                        <div>
                            <InputLabel for="instructions" value="Instructions (one step per line)" />
                            <textarea
                                id="instructions"
                                v-model="form.instructions"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-600 dark:focus:ring-brand-600"
                                rows="6"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.instructions" />
                        </div>

                        <!-- Sources -->
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sources</h3>
                                <button
                                    type="button"
                                    @click="addSource"
                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                                >
                                    Add Source
                                </button>
                            </div>

                            <div
                                v-for="(source, index) in form.sources"
                                :key="index"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3"
                            >
                                <div class="flex justify-between items-center">
                                    <h4 class="font-medium text-gray-900 dark:text-white">Source {{ index + 1 }}</h4>
                                    <button
                                        type="button"
                                        @click="removeSource(index)"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                    >
                                        Remove
                                    </button>
                                </div>

                                <div>
                                    <InputLabel :for="`source_title_${index}`" value="Title" />
                                    <TextInput
                                        :id="`source_title_${index}`"
                                        v-model="source.title"
                                        type="text"
                                        placeholder="e.g. Vegan Fat Loss Meal Plan PDF"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors[`sources.${index}.title`]" />
                                </div>

                                <div>
                                    <InputLabel :for="`source_url_${index}`" value="URL" />
                                    <TextInput
                                        :id="`source_url_${index}`"
                                        v-model="source.url"
                                        type="text"
                                        placeholder="https://..."
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors[`sources.${index}.url`]" />
                                </div>

                                <div>
                                    <InputLabel :for="`source_type_${index}`" value="Type" />
                                    <select
                                        :id="`source_type_${index}`"
                                        v-model="source.type"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 dark:focus:border-brand-600 focus:ring-brand-500 dark:focus:ring-brand-600 rounded-md shadow-sm"
                                    >
                                        <option value="">-</option>
                                        <option value="pdf">PDF</option>
                                        <option value="video">Video</option>
                                        <option value="website">Website</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors[`sources.${index}.type`]" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <Link href="/recipes">
                                <SecondaryButton>Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Save Changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
