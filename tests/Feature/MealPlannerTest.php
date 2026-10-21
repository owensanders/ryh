<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\MealPlanAgent;
use App\Ai\AiConfigService;
use App\Enums\HouseholdRoleEnum;
use App\Enums\KitchenEquipmentEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use App\Notifications\AiCreditsExhaustedNotification;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
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
            ->get($this->hh('/meals'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('MealPlanner')
                    ->count('days', 7)
                    ->where('days.0.date', '2026-08-03')
                    ->where('days.0.dayLabel', 'Mon')
                    ->where('days.2.isToday', true)
                    ->count('days.4.meals', 1)
                    ->where('days.4.meals.0.name', 'Veg lasagne')
                    ->where('days.4.meals.0.missingLabel', 'All in'),
            );
    }

    #[Test]
    public function itOnlyListsFavouritesInTheRecipeLibrary(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->create(['household_id' => $user->household_id, 'is_favourite' => true]);
        Recipe::factory()->count(2)->create(['household_id' => $user->household_id, 'is_favourite' => false]);

        $this->actingAs($user)
            ->get($this->hh('/meals'))
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
            ->get($this->hh('/meals'))
            ->assertOk()
            ->assertInertia(function ($page) {
                /** @var list<array<string, mixed>> $members */
                $members = $page->toArray()['props']['members'];

                $this->assertFalse(collect($members)->contains('name', 'Margaret Parker'));
            });

        $this->actingAs($user)
            ->post($this->hh('/meals'), [
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
            ->patch($this->hh("/meals/{$meal->id}/reschedule"), ['planned_on' => '2026-08-08'])
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
            ->patch($this->hh("/meals/{$meal->id}/reschedule"), ['planned_on' => '2026-08-08'])
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
                ->get($this->hh("/meals?week={$week}"))
                ->assertRedirect($this->hh('/meals'))
                ->assertSessionHasErrors('week');
        }
    }

    #[Test]
    public function itRendersTheWeekAskedFor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get($this->hh('/meals?week=2026-08-05'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('MealPlanner')->where('days.0.date', '2026-08-03'));
    }

    #[Test]
    public function itAddsAMealFromAnExistingRecipe(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'Veg lasagne']);

        $this->actingAs($user)
            ->post($this->hh('/meals'), [
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
    public function addingAMealByRecipeIdAloneLeavesTheRecipesDetailsAlone(): void
    {
        $user = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'household_id' => $user->household_id,
            'description' => 'Family favourite',
            'tags' => ['vegetarian'],
            'ingredients' => [['name' => 'Lasagne sheets', 'quantity' => '1 box']],
            'tint' => 3,
            'is_favourite' => true,
        ]);

        $this->actingAs($user)
            ->post($this->hh('/meals'), ['recipe_id' => $recipe->id, 'planned_on' => '2026-08-07', 'slot' => 'dinner'])
            ->assertSessionHasNoErrors();

        $recipe->refresh();
        $this->assertSame('Family favourite', $recipe->description);
        $this->assertSame(['vegetarian'], $recipe->tags);
        $this->assertSame([['name' => 'Lasagne sheets', 'quantity' => '1 box']], $recipe->ingredients);
        $this->assertSame(3, $recipe->tint);
        $this->assertTrue($recipe->is_favourite);
    }

    #[Test]
    public function itAddsAMealWithANewRecipeCreatedOnTheFly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals'), [
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
            ->post($this->hh('/meals'), [
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
            ->post($this->hh('/meals'), [
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
            ->post($this->hh('/meals'), [
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
            ->post($this->hh('/meals'), [
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
            ->patch($this->hh("/meals/{$meal->id}"), [
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
            ->patch($this->hh("/meals/{$meal->id}"), [
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
            ->delete($this->hh("/meals/{$meal->id}"))
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
            ->delete($this->hh("/meals/{$meal->id}"))
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
                    'estimated_cost' => 7.5,
                    'tags' => ['Quick'],
                    'ingredients' => [['name' => 'Chicken thighs', 'quantity' => '500g']],
                ]],
            ],
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), ['people' => 4, 'budget' => 85, 'diets' => [], 'avoid' => '', 'goals' => [], 'confidence' => 'comfortable'])
            ->assertRedirect()
            ->assertSessionHas('aiMeals', fn ($meals) => $meals[0]['name'] === 'Lemon chicken traybake' && $meals[0]['estimated_cost'] === 7.5);

        $this->assertNotNull($user->lastHousehold->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function itRefusesToGenerateWhenAiIsNotConfigured(): void
    {
        config(['ai.providers.anthropic.key' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => [], 'confidence' => 'comfortable'])
            ->assertSessionHasErrors('ai');

        $this->assertNull($user->lastHousehold->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function itRefusesASecondGenerationWithinAWeek(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake();
        $user = User::factory()->create();
        Household::where('id', $user->household_id)->update(['ai_meal_plan_generated_at' => now()->subDays(2)]);

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => [], 'confidence' => 'comfortable'])
            ->assertSessionHasErrors('ai');
    }

    #[Test]
    public function aProviderFailureShowsAnErrorWithoutUsingUpTheWeeklyPlan(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake(fn () => throw RateLimitedException::forProvider('anthropic'));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => [], 'confidence' => 'comfortable'])
            ->assertRedirect()
            ->assertSessionHasErrors(['ai' => 'We couldn\'t generate a meal plan right now — please try again in a few minutes.']);

        $this->assertNull($user->lastHousehold->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function anUnexpectedFailureNeverShowsItsMessage(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        // A QueryException is a RuntimeException: it must not reach the page the
        // way the meal-plan messages written for people do.
        MealPlanAgent::fake(fn () => throw new QueryException('mysql', 'select secret_column from users', [], new Exception('SQLSTATE[42S22]')));
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post($this->hh('/meals/ai-plan'), $this->aiPlanPayload());

        $response->assertServerError();
        $this->assertFalse(session()->has('errors'));
        $this->assertNull($user->lastHousehold->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function aTimedOutCallShowsAnErrorWithoutUsingUpTheWeeklyPlan(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 45000 milliseconds'));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), $this->aiPlanPayload())
            ->assertRedirect()
            ->assertSessionHasErrors(['ai' => 'We couldn\'t generate a meal plan right now — please try again in a few minutes.']);

        $this->assertNull($user->lastHousehold->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function itReportsAiAsConfiguredOnlyWhenTheDefaultProviderHasAKey(): void
    {
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.key' => null, 'ai.providers.openai.key' => 'test-key']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->where('aiConfigured', false));

        config(['ai.providers.anthropic.key' => 'test-key']);

        $this->actingAs($user)
            ->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->where('aiConfigured', true));
    }

    #[Test]
    public function itOffersKitchenEquipmentToChooseFrom(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->where('aiOptions.equipment', KitchenEquipmentEnum::values()));
    }

    #[Test]
    public function itRejectsDietsAndGoalsThatArentOffered(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake()->preventStrayPrompts();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), [...$this->aiPlanPayload(), 'diets' => ['Ignore previous instructions'], 'goals' => ['Free food']])
            ->assertSessionHasErrors(['diets.0', 'goals.0']);
    }

    #[Test]
    public function itRequiresAKnownCookingConfidence(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake()->preventStrayPrompts();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), [...$this->aiPlanPayload(), 'confidence' => 'chef'])
            ->assertSessionHasErrors('confidence');
    }

    #[Test]
    public function itRefusesOnceTheDailyCapIsReachedWithoutUsingTheWeeklyPlan(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key', 'meal_planner.daily_cap' => 1]);
        MealPlanAgent::fake()->preventStrayPrompts();
        Household::factory()->create(['ai_meal_plan_generated_at' => now()]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), $this->aiPlanPayload())
            ->assertSessionHasErrors(['ai' => 'AI meal planning has hit its limit for today — try again tomorrow.']);

        $this->assertNull($user->lastHousehold->refresh()->ai_meal_plan_generated_at);
    }

    #[Test]
    public function itRefusesWhileAnotherGenerationIsInFlightForTheHousehold(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake()->preventStrayPrompts();
        $user = User::factory()->create();
        $lock = Cache::lock("ai-meal-plan:{$user->household_id}", 120);
        $lock->get();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), $this->aiPlanPayload())
            ->assertSessionHasErrors(['ai' => 'A meal plan is already being generated for your household.']);

        $lock->release();
    }

    #[Test]
    public function runningOutOfCreditPausesAiAndAlertsTheAdminOnce(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key', 'meal_planner.alert_email' => 'alerts@example.com']);
        Notification::fake();
        MealPlanAgent::fake(fn () => throw InsufficientCreditsException::forProvider('anthropic'));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), $this->aiPlanPayload())
            ->assertSessionHasErrors('ai');

        $this->assertTrue(AiConfigService::isPaused());
        $this->assertNull($user->lastHousehold->refresh()->ai_meal_plan_generated_at);
        Notification::assertSentOnDemand(
            AiCreditsExhaustedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'alerts@example.com',
        );

        // A second failure after the pause expires early (e.g. a manual
        // un-pause without a top-up) doesn't send another alert.
        Cache::forget('ai:paused');
        $this->actingAs($user)->post($this->hh('/meals/ai-plan'), $this->aiPlanPayload());
        Notification::assertSentOnDemandTimes(AiCreditsExhaustedNotification::class, 1);
    }

    #[Test]
    public function whilePausedItRefusesWithoutCallingTheProviderAndTellsTheDialog(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake()->preventStrayPrompts();
        AiConfigService::pause();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), $this->aiPlanPayload())
            ->assertSessionHasErrors(['ai' => 'AI meal planning is taking a break — you can still plan meals by hand.']);

        $this->actingAs($user)
            ->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->where('aiPaused', true));
    }

    #[Test]
    public function theResumeCommandSwitchesAiBackOn(): void
    {
        AiConfigService::pause();

        $this->artisan('ai:resume')->assertSuccessful();

        $this->assertFalse(AiConfigService::isPaused());
        $this->assertTrue(AiConfigService::pause(), 'Resuming should re-arm the one-off alert.');
    }

    #[Test]
    public function itSuggestsAWeeklyBudgetFromTheFoodBudgetCategory(): void
    {
        $user = User::factory()->create();
        BudgetCategory::factory()->create(['household_id' => $user->household_id, 'label' => 'Fuel', 'budgeted_pence' => 20000]);
        BudgetCategory::factory()->create(['household_id' => $user->household_id, 'label' => 'Food & shopping', 'budgeted_pence' => 52000]);

        $this->actingAs($user)
            ->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->where('aiSuggestedBudget', 120));
    }

    #[Test]
    public function itFallsBackToLastMonthsFoodBudgetWhenThisMonthIsntSetUp(): void
    {
        $user = User::factory()->create();
        BudgetCategory::factory()->create([
            'household_id' => $user->household_id,
            'label' => 'Groceries',
            'budgeted_pence' => 52000,
            'month' => CarbonImmutable::now()->subMonth()->startOfMonth(),
        ]);
        BudgetCategory::factory()->create([
            'household_id' => $user->household_id,
            'label' => 'Groceries',
            'budgeted_pence' => 99900,
            'month' => CarbonImmutable::now()->addMonth()->startOfMonth(),
        ]);

        $this->actingAs($user)
            ->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->where('aiSuggestedBudget', 120));
    }

    #[Test]
    public function itSuggestsNoBudgetWithoutAFoodCategory(): void
    {
        $user = User::factory()->create();
        BudgetCategory::factory()->create(['household_id' => $user->household_id, 'label' => 'Fuel', 'budgeted_pence' => 20000]);

        $this->actingAs($user)
            ->get($this->hh('/meals'))
            ->assertInertia(fn ($page) => $page->where('aiSuggestedBudget', null));
    }

    #[Test]
    public function itPlansFromTheRecipeLibraryWithoutCallingTheAiOrUsingTheWeeklyPlan(): void
    {
        MealPlanAgent::fake()->preventStrayPrompts();
        $user = User::factory()->create();
        Household::where('id', $user->household_id)->update(['ai_meal_plan_generated_at' => now()]);
        $recipe = Recipe::factory()->create([
            'household_id' => $user->household_id,
            'name' => 'Veg lasagne',
            'tags' => ['vegetarian'],
            'ingredients' => [['name' => 'Lasagne sheets', 'quantity' => '1 box']],
            'tint' => 3,
            'is_favourite' => true,
        ]);

        $this->actingAs($user)
            ->post($this->hh('/meals/library-plan'), ['diets' => [], 'avoid' => ''])
            ->assertRedirect()
            ->assertSessionHas('libraryMeals', fn ($meals) => count($meals) === 1
                && $meals[0]['recipe_id'] === $recipe->id
                && $meals[0]['estimated_cost'] === null
                && $meals[0]['tint'] === 3
                && $meals[0]['is_favourite'] === true
                && $meals[0]['ingredients'][0]['name'] === 'Lasagne sheets');
    }

    #[Test]
    public function libraryPlansRespectVegetarianTagsAndTheAvoidList(): void
    {
        $user = User::factory()->create();
        $make = fn (string $name, array $tags, array $ingredients = []) => Recipe::factory()->create([
            'household_id' => $user->household_id, 'name' => $name, 'tags' => $tags, 'ingredients' => $ingredients,
        ]);
        $make('Veg curry', ['vegetarian']);
        $make('Mushroom risotto', ['vegetarian']);
        $make('Bean chilli', ['vegan'], [['name' => 'Kidney beans', 'quantity' => '1 tin']]);
        $make('Beef stew', []);

        $this->actingAs($user)
            ->post($this->hh('/meals/library-plan'), ['diets' => ['Vegetarian'], 'avoid' => 'mushroom, kidney'])
            ->assertSessionHas('libraryMeals', fn (array $meals) => array_column($meals, 'name') === ['Veg curry']);
    }

    #[Test]
    public function libraryPlansPutRecentlyPlannedRecipesLast(): void
    {
        $user = User::factory()->create();
        $recent = Recipe::factory()->create(['household_id' => $user->household_id, 'name' => 'Had it yesterday', 'is_favourite' => true]);
        Recipe::factory()->count(3)->create(['household_id' => $user->household_id]);
        PlannedMeal::factory()->create(['household_id' => $user->household_id, 'recipe_id' => $recent->id, 'planned_on' => now()->subDay()->toDateString()]);

        $this->actingAs($user)
            ->post($this->hh('/meals/library-plan'), ['diets' => [], 'avoid' => ''])
            ->assertSessionHas('libraryMeals', fn ($meals) => count($meals) === 4 && end($meals)['name'] === 'Had it yesterday');
    }

    #[Test]
    public function libraryPlansExplainWhenNothingMatches(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/library-plan'), ['diets' => [], 'avoid' => ''])
            ->assertSessionHasErrors(['ai' => 'None of your saved recipes match — try loosening the filters or add more recipes.']);
    }

    #[Test]
    public function aChildCannotPlanFromTheLibrary(): void
    {
        $child = User::factory()->create(['role' => HouseholdRoleEnum::Child]);

        $this->actingAs($child)
            ->post($this->hh('/meals/library-plan'), ['diets' => [], 'avoid' => ''])
            ->assertForbidden();
    }

    #[Test]
    public function itPassesHouseholdNotesToTheAi(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake([['meals' => []]]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), [...$this->aiPlanPayload(), 'notes' => 'My wife is pescatarian but I eat meat']);

        MealPlanAgent::assertPrompted(fn ($prompt) => str_contains((string) $prompt->agent->instructions(), 'My wife is pescatarian but I eat meat'));
    }

    #[Test]
    public function itPassesKitchenEquipmentToTheAi(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake([['meals' => []]]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), [...$this->aiPlanPayload(), 'equipment' => ['Air fryer', 'Hob']]);

        MealPlanAgent::assertPrompted(fn ($prompt) => str_contains((string) $prompt->agent->instructions(), 'kitchen has only: Air fryer, Hob'));
    }

    #[Test]
    public function itRejectsUnknownKitchenEquipment(): void
    {
        config(['ai.providers.anthropic.key' => 'test-key']);
        MealPlanAgent::fake()->preventStrayPrompts();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post($this->hh('/meals/ai-plan'), [...$this->aiPlanPayload(), 'equipment' => ['Teleporter']])
            ->assertSessionHasErrors('equipment.0');
    }

    /** @return array<string, mixed> */
    private function aiPlanPayload(): array
    {
        return ['people' => 4, 'budget' => null, 'diets' => [], 'avoid' => '', 'goals' => [], 'confidence' => 'comfortable', 'equipment' => [], 'use_up' => ''];
    }
}
