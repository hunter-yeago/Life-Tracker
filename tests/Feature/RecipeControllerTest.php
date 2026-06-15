<?php

namespace Tests\Feature;

use App\Models\FoodType;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_recipes_with_sources(): void
    {
        $user = User::factory()->create();
        $foodType = FoodType::factory()->create();

        $recipe = Recipe::factory()->create(['food_type_id' => $foodType->id]);
        $recipe->sources()->create([
            'title' => 'Example PDF',
            'url' => 'https://example.com/recipe.pdf',
            'type' => 'pdf',
        ]);

        $response = $this->actingAs($user)->get('/recipes');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Recipes/Index')
            ->where('recipes.0.name', $recipe->name)
            ->where('recipes.0.sources.0.title', 'Example PDF')
        );
    }

    public function test_store_creates_recipe_with_sources(): void
    {
        $user = User::factory()->create();
        $foodType = FoodType::factory()->create();

        $response = $this->actingAs($user)->post('/recipes', [
            'food_type_id' => $foodType->id,
            'name' => 'Berry Overnight Oats',
            'description' => 'Tasty oats',
            'ingredients' => "Oats\nSoy milk",
            'instructions' => "Mix\nChill overnight",
            'servings' => '4 servings',
            'sources' => [
                ['title' => 'PDF Source', 'url' => 'https://example.com/plan.pdf', 'type' => 'pdf'],
                ['title' => 'YouTube Source', 'url' => 'https://youtube.com/watch?v=abc', 'type' => 'video'],
            ],
        ]);

        $response->assertRedirect('/recipes');

        $recipe = Recipe::where('name', 'Berry Overnight Oats')->first();
        $this->assertNotNull($recipe);
        $this->assertSame($foodType->id, $recipe->food_type_id);
        $this->assertCount(2, $recipe->sources);
    }

    public function test_update_replaces_recipe_sources(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();
        $recipe->sources()->create(['title' => 'Old Source', 'url' => null, 'type' => null]);

        $response = $this->actingAs($user)->put("/recipes/{$recipe->id}", [
            'name' => 'Updated Name',
            'sources' => [
                ['title' => 'New Source', 'url' => 'https://example.com', 'type' => 'website'],
            ],
        ]);

        $response->assertRedirect('/recipes');

        $recipe->refresh();
        $this->assertSame('Updated Name', $recipe->name);
        $this->assertCount(1, $recipe->sources);
        $this->assertSame('New Source', $recipe->sources->first()->title);
    }

    public function test_destroy_deletes_recipe_and_sources(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create();
        $recipe->sources()->create(['title' => 'Source', 'url' => null, 'type' => null]);

        $response = $this->actingAs($user)->delete("/recipes/{$recipe->id}");

        $response->assertRedirect('/recipes');
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
        $this->assertDatabaseMissing('recipe_sources', ['recipe_id' => $recipe->id]);
    }
}
