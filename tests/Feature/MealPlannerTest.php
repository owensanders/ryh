<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\MealPlanAgent;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Exceptions\RateLimitedException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MealPlannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin to a Wednesday so week boundaries are unambiguous.
        CarbonImmutable::setTestNow('2026-08-05 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function itRendersSevenDaysStartingOnMondayAndFilesMealsUnderTheirDay(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'Veg lasagne']);

        PlannedMeal::factory()->create([
            'household_id' => $user->household_id,
            'recipe_id' => $recipe->id,
            'cook_id' => $user->id,
            'planned_on' => '2026-08-07',
        ]);

        $this->actingAs($user)
            ->get('/meals')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MealPlanner')
                ->count('days', 7)
                ->where('days.0.date', '2026-08-03')
                ->where('days.0.dayLabel', 'Mon')
                ->where('days.2.isToday', true)
                ->count('days.4.meals', 1)
                ->where('days.4.meals.0.name', 'Veg lasagne')
                ->where('days.4.meals.0.missingLabel', 'All in')
            );
    }

    #[Test]
    public function itOnlyListsFavouritesInTheRecipeLibrary(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->create(['household_id' => $user->household_id, 'is_favourite' => true]);
        Recipe::factory()->count(2)->create(['household_id' => $user->household_id, 'is_favourite' => false]);

        $this->actingAs($user)
            ->get('/meals')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->count('library', 1)->where('recipeCount', 3));
    }

    #[Test]
    public function aPendingMemberDoesNotAppearInTheCookDropdownAndCannotBeAssigned(): void
    {
        $user = User::factory()->create();
        $pending = User::factory()->create(['household_id' => $user->household_id, 'name' => 'Margaret Parker', 'pending' => true]);
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'Veg lasagne']);

        $this->actingAs($user)
            ->get('/meals')
            ->assertOk()
            ->assertInertia(function ($page) {
                /** @var list<array<string, mixed>> $members */
                $members = $page->toArray()['props']['members'];

                $this->assertFalse(collect($members)->contains('name', 'Margaret Parker'));
            });

        $this->actingAs($user)
            ->post('/meals', [
                'recipe_id' => $recipe->id,
                'planned_on' => '2026-08-07',
                'slot' => 'dinner',
                'cook_id' => $pending->id,
            ])
            ->assertSessionHasErrors('cook_id');
    }

    #[Test]
    public function itReschedulesAMealToAnotherDay(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'BBQ burgers']);
        $meal = PlannedMeal::factory()->create([
            'household_id' => $user->household_id,
            'recipe_id' => $recipe->id,
            'planned_on' => '2026-08-05',
        ]);

        $this->actingAs($user)
            ->patch("/meals/{$meal->id}/reschedule", ['planned_on' => '2026-08-08'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'BBQ burgers moved');

        $this->assertSame('2026-08-08', $meal->refresh()->planned_on->toDateString());
    }

    #[Test]
    public function itDoesNotRescheduleAnotherHouseholdsMeal(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $meal = PlannedMeal::factory()->create([
            'household_id' => $stranger->household_id,
            'planned_on' => '2026-08-05',
        ]);

        $this->actingAs($user)
            ->patch("/meals/{$meal->id}/reschedule", ['planned_on' => '2026-08-08'])
            ->assertNotFound();

        $this->assertSame('2026-08-05', $meal->refresh()->planned_on->toDateString());
    }

    #[Test]
    public function itRejectsAWeekItCannotUnderstand(): void
    {
        $user = User::factory()->create();

        // `?week=garbage` used to reach Carbon and 500.
        foreach (['garbage', '2026-13-01', '08/08/2026'] as $week) {
            $this->actingAs($user)
                ->get("/meals?week={$week}")
                ->assertRedirect('/meals')
                ->assertSessionHasErrors('week');
        }
    }

    #[Test]
    public function itRendersTheWeekAskedFor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/meals?week=2026-08-05')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('MealPlanner')->where('days.0.date', '2026-08-03'));
    }

    #[Test]
    public function itAddsAMealFromAnExistingRecipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'Veg lasagne']);

        $this->actingAs($user)
            ->post('/meals', [
                'recipe_id' => $recipe->id,
                'planned_on' => '2026-08-07',
                'slot' => 'dinner',
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Veg lasagne added');

        $this->assertDatabaseHas('planned_meals', [
            'household_id' => $user->household_id,
            'recipe_id' => $recipe->id,
            'planned_on' => '2026-08-07',
        ]);
    }

    #[Test]
    public function itAddsAMealWithANewRecipeCreatedOnTheFly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/meals', [
                'new_recipe_name' => 'Sunday roast',
                'new_recipe_description' => 'Beef, all the trimmings',
                'planned_on' => '2026-08-07',
                'slot' => 'dinner',
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Sunday roast added');

        $this->assertDatabaseHas('recipes', [
            'household_id' => $user->household_id,
            'name' => 'Sunday roast',
            'description' => 'Beef, all the trimmings',
        ]);
        $this->assertDatabaseHas('planned_meals', ['household_id' => $user->household_id, 'planned_on' => '2026-08-07']);
    }

    #[Test]
    public function itAddsAMealWithANewRecipeIncludingFullDetails(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/meals', [
                'new_recipe_name' => 'Veg curry',
                'new_recipe_duration_label' => '40 min',
                'new_recipe_difficulty' => 'Medium',
                'new_recipe_tags' => ['vegetarian', 'spicy'],
                'new_recipe_ingredients' => [
                    ['name' => 'Chickpeas', 'quantity' => '1 tin'],
                    ['name' => 'Coconut milk', 'quantity' => null],
                ],
                'new_recipe_tint' => 2,
                'new_recipe_is_favourite' => true,
                'planned_on' => '2026-08-07',
                'slot' => 'dinner',
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Veg curry added');

        $recipe = Recipe::where('name', 'Veg curry')->sole();
        $this->assertSame('40 min', $recipe->duration_label);
        $this->assertSame(['vegetarian', 'spicy'], $recipe->tags);
        $this->assertSame('Chickpeas', $recipe->ingredients[0]['name']);
        $this->assertTrue($recipe->is_favourite);
    }

    #[Test]
    public function itUpdatesTheSelectedExistingRecipesDetailsWhenAddingAMeal(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'household_id' => $user->household_id,
            'name' => 'Fish tacos',
            'duration_label' => '20 min',
        ]);

        $this->actingAs($user)
            ->post('/meals', [
                'recipe_id' => $recipe->id,
                'new_recipe_duration_label' => '25 min',
                'new_recipe_ingredients' => [['name' => 'Cod fillets', 'quantity' => '400g']],
                'planned_on' => '2026-08-07',
                'slot' => 'dinner',
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Fish tacos added');

        $recipe->refresh();
        $this->assertSame('25 min', $recipe->duration_label);
        $this->assertSame('Cod fillets', $recipe->ingredients[0]['name']);
    }

    #[Test]
    public function itPushesIngredientsToAShoppingListWhenAddingAMeal(): void
    {
        $user = User::factory()->create();
        $list = ShoppingList::factory()->create(['household_id' => $user->household_id]);

        $this->actingAs($user)
            ->post('/meals', [
                'new_recipe_name' => 'Veg curry',
                'new_recipe_ingredients' => [['name' => 'Chickpeas', 'quantity' => '1 tin']],
                'new_recipe_shopping_list_id' => $list->id,
                'planned_on' => '2026-08-07',
                'slot' => 'dinner',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shopping_items', ['shopping_list_id' => $list->id, 'name' => 'Chickpeas', 'quantity' => '1 tin']);
    }

    #[Test]
    public function itRejectsADuplicateNewRecipeNameInTheSameHousehold(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'Fish tacos']);

        $this->actingAs($user)
            ->post('/meals', [
                'new_recipe_name' => 'Fish tacos',
                'planned_on' => '2026-08-07',
                'slot' => 'dinner',
            ])
            ->assertSessionHasErrors('new_recipe_name');
    }

    #[Test]
    public function itUpdatesAMeal(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id]);
        $otherRecipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'Fish tacos']);
        $meal = PlannedMeal::factory()->create([
            'household_id' => $user->household_id,
            'recipe_id' => $recipe->id,
            'planned_on' => '2026-08-05',
            'slot' => 'dinner',
        ]);

        $this->actingAs($user)
            ->patch("/meals/{$meal->id}", [
                'recipe_id' => $otherRecipe->id,
                'planned_on' => '2026-08-05',
                'slot' => 'lunch',
            ])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Fish tacos updated');

        $meal->refresh();
        $this->assertSame($otherRecipe->id, $meal->recipe_id);
        $this->assertSame('lunch', $meal->slot->value);
    }

    #[Test]
    public function itDoesNotUpdateAnotherHouseholdsMeal(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id]);
        $stranger = User::factory()->create();
        $meal = PlannedMeal::factory()->create(['household_id' => $stranger->household_id, 'planned_on' => '2026-08-05']);

        $this->actingAs($user)
            ->patch("/meals/{$meal->id}", [
                'recipe_id' => $recipe->id,
                'planned_on' => '2026-08-06',
                'slot' => 'dinner',
            ])
            ->assertNotFound();
    }

    #[Test]
    public function itDeletesAMeal(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'BBQ burgers']);
        $meal = PlannedMeal::factory()->create(['household_id' => $user->household_id, 'recipe_id' => $recipe->id]);

        $this->actingAs($user)
            ->delete("/meals/{$meal->id}")
            ->assertRedirect()
            ->assertSessionHas('toast', 'BBQ burgers removed');

        $this->assertDatabaseMissing('planned_meals', ['id' => $meal->id]);
    }

    #[Test]
    public function itDoesNotDeleteAnotherHouseholdsMeal(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $meal = PlannedMeal::factory()->create(['household_id' => $stranger->household_id]);

        $this->actingAs($user)
            ->delete("/meals/{$meal->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('planned_meals', ['id' => $meal->id]);
    }

    #[Test]
    public function itGeneratesAnAiMealPlanWhenConfigured(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake([
            [
                'meals' => [[
                    'name' => 'Lemon chicken traybake',
                    'description' => 'A simple one-tray dinner.',
                    'duration_label' => '45 min',
                    'difficulty' => 'Easy',
                    'tags' => ['Quick'],
                    'ingredients' => [['name' => 'Chicken thighs', 'quantity' => '500g']],
                ]],
            ],
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/meals/ai-plan', ['people' => 4, 'budget' => 85, 'diets' => [], 'avoid' => '', 'goals' => []])
            ->assertRedirect()
            ->assertSessionHas('aiMeals', fn ($meals) => $meals[0]['name'] === 'Lemon chicken traybake');

        $this->assertNotNull($user->household->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function itRefusesToGenerateWhenAiIsNotConfigured(): void
    {
        config(['ai.providers.anthropic.key' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/meals/ai-plan', ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => []])
            ->assertSessionHasErrors('ai');

        $this->assertNull($user->household->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function itRefusesASecondGenerationWithinAWeek(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake();
        $user = User::factory()->create();
        Household::where('id', $user->household_id)->update(['ai_meal_plan_generated_at' => now()->subDays(2)]);

        $this->actingAs($user)
            ->post('/meals/ai-plan', ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => []])
            ->assertSessionHasErrors('ai');
    }

    #[Test]
    public function aProviderFailureShowsAnErrorWithoutUsingUpTheWeeklyPlan(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake(fn () => throw RateLimitedException::forProvider('anthropic'));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/meals/ai-plan', ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => []])
            ->assertRedirect()
            ->assertSessionHasErrors(['ai' => 'We couldn\'t generate a meal plan right now — please try again in a few minutes.']);

        $this->assertNull($user->household->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function itReportsAiAsConfiguredOnlyWhenTheDefaultProviderHasAKey(): void
    {
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.key' => null, 'ai.providers.openai.key' => 'test-key']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/meals')
            ->assertInertia(fn ($page) => $page->where('aiConfigured', false));

        config(['ai.providers.anthropic.key' => 'test-key']);

        $this->actingAs($user)
            ->get('/meals')
            ->assertInertia(fn ($page) => $page->where('aiConfigured', true));
    }
}
