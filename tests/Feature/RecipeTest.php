<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRendersTheWholeLibraryNotJustFavourites(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->create(['household_id' => $user->household_id, 'is_favourite' => true]);
        Recipe::factory()->count(2)->create(['household_id' => $user->household_id, 'is_favourite' => false]);

        $this->actingAs($user)
            ->get($this->hh('/recipes'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Recipes')->count('recipes', 3));
    }

    #[Test]
    public function itCreatesARecipe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/recipes'), [
                'name' => 'Veg lasagne',
                'duration_label' => '45 min',
                'difficulty' => 'Medium',
                'tags' => ['vegetarian'],
                'tint' => 2,
                'is_favourite' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Veg lasagne added');

        $this->assertDatabaseHas('recipes', [
            'household_id' => $user->household_id,
            'name' => 'Veg lasagne',
            'is_favourite' => true,
        ]);
    }

    #[Test]
    public function itUpdatesARecipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'BBQ burgers']);

        $this->actingAs($user)
            ->patch($this->hh("/recipes/{$recipe->id}"), [
                'name' => 'BBQ beef burgers',
                'is_favourite' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'BBQ beef burgers updated');

        $this->assertSame('BBQ beef burgers', $recipe->refresh()->name);
        $this->assertTrue($recipe->is_favourite);
    }

    #[Test]
    public function itDoesNotUpdateAnotherHouseholdsRecipe(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $stranger->household_id, 'name' => 'BBQ burgers']);

        $this->actingAs($user)
            ->patch($this->hh("/recipes/{$recipe->id}"), ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertSame('BBQ burgers', $recipe->refresh()->name);
    }

    #[Test]
    public function itDeletesARecipeAndCascadesItsPlannedMeals(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'BBQ burgers']);
        PlannedMeal::factory()->create(['household_id' => $user->household_id, 'recipe_id' => $recipe->id]);

        $this->actingAs($user)
            ->delete($this->hh("/recipes/{$recipe->id}"))
            ->assertRedirect()
            ->assertSessionHas('toast', 'BBQ burgers deleted');

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
        $this->assertDatabaseMissing('planned_meals', ['recipe_id' => $recipe->id]);
    }

    #[Test]
    public function itDoesNotDeleteAnotherHouseholdsRecipe(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $stranger->household_id]);

        $this->actingAs($user)
            ->delete($this->hh("/recipes/{$recipe->id}"))
            ->assertNotFound();

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id]);
    }

    #[Test]
    public function itAddsARecipesIngredientsToAShoppingList(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'household_id' => $user->household_id,
            'ingredients' => [
                ['name' => 'Chicken breast', 'quantity' => '500g'],
                ['name' => 'Lemon', 'quantity' => null],
            ],
        ]);
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);

        $this->actingAs($user)
            ->post($this->hh("/recipes/{$recipe->id}/add-to-shopping-list"), ['shopping_list_id' => $list->id])
            ->assertRedirect();

        $this->assertDatabaseHas('shopping_items', ['shopping_list_id' => $list->id, 'name' => 'Chicken breast', 'quantity' => '500g']);
        $this->assertDatabaseHas('shopping_items', ['shopping_list_id' => $list->id, 'name' => 'Lemon']);
    }

    #[Test]
    public function itSkipsIngredientsAlreadyOnTheList(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'household_id' => $user->household_id,
            'ingredients' => [
                ['name' => 'Lemon', 'quantity' => '1'],
                ['name' => 'Chicken breast', 'quantity' => '500g'],
            ],
        ]);
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);
        $list->items()->create(['name' => 'lemon', 'quantity' => '2', 'position' => 0]);

        $this->actingAs($user)
            ->post($this->hh("/recipes/{$recipe->id}/add-to-shopping-list"), ['shopping_list_id' => $list->id])
            ->assertRedirect();

        $this->assertSame(1, $list->items()->where('name', 'lemon')->count());
        $this->assertDatabaseHas('shopping_items', ['shopping_list_id' => $list->id, 'name' => 'Chicken breast']);
    }

    #[Test]
    public function itAddsIngredientsToAListWhenCreatingARecipe(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);

        $this->actingAs($user)
            ->post($this->hh('/recipes'), [
                'name' => 'Veg curry',
                'ingredients' => [['name' => 'Chickpeas', 'quantity' => '1 tin']],
                'shopping_list_id' => $list->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Veg curry added');

        $this->assertDatabaseHas('shopping_items', ['shopping_list_id' => $list->id, 'name' => 'Chickpeas', 'quantity' => '1 tin']);
    }

    #[Test]
    public function itRejectsADuplicateRecipeNameInTheSameHousehold(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'BBQ burgers']);

        $this->actingAs($user)
            ->post($this->hh('/recipes'), ['name' => 'BBQ burgers'])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function itAllowsTheSameRecipeNameInADifferentHousehold(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        Recipe::factory()->create(['household_id' => $stranger->household_id, 'name' => 'BBQ burgers']);

        $this->actingAs($user)
            ->post($this->hh('/recipes'), ['name' => 'BBQ burgers'])
            ->assertSessionDoesntHaveErrors('name');
    }
}
