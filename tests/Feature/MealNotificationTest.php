<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealDeletedNotification;
use App\Notifications\MealEditedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MealNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function addingAMealAssignedToSomeoneElseNotifiesThem(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $cook = User::factory()->create(['household_id' => $owner->household_id]);
        $recipe = Recipe::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->post('/meals', [
            'recipe_id' => $recipe->id,
            'planned_on' => now()->toDateString(),
            'slot' => 'dinner',
            'cook_id' => $cook->id,
        ]);

        Notification::assertSentTo($cook, MealAssignedNotification::class);
        Notification::assertNotSentTo($owner, MealAssignedNotification::class);
    }

    #[Test]
    public function addingAMealAssignedToYourselfDoesNotNotifyYou(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->post('/meals', [
            'recipe_id' => $recipe->id,
            'planned_on' => now()->toDateString(),
            'slot' => 'dinner',
            'cook_id' => $owner->id,
        ]);

        Notification::assertNothingSent();
    }

    #[Test]
    public function reassigningAMealsCookNotifiesTheNewCookOnly(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $first = User::factory()->create(['household_id' => $owner->household_id]);
        $second = User::factory()->create(['household_id' => $owner->household_id]);
        $recipe = Recipe::factory()->create(['household_id' => $owner->household_id]);
        $meal = PlannedMeal::factory()->create([
            'household_id' => $owner->household_id,
            'recipe_id' => $recipe->id,
            'cook_id' => $first->id,
        ]);

        $this->actingAs($owner)->patch("/meals/{$meal->id}", [
            'recipe_id' => $recipe->id,
            'planned_on' => $meal->planned_on->toDateString(),
            'slot' => $meal->slot->value,
            'cook_id' => $second->id,
        ]);

        Notification::assertSentTo($second, MealAssignedNotification::class);
        Notification::assertNotSentTo($first, MealAssignedNotification::class);
    }

    #[Test]
    public function editingAMealWithoutChangingTheCookNotifiesTheCook(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $cook = User::factory()->create(['household_id' => $owner->household_id]);
        $recipe = Recipe::factory()->create(['household_id' => $owner->household_id]);
        $meal = PlannedMeal::factory()->create([
            'household_id' => $owner->household_id,
            'recipe_id' => $recipe->id,
            'cook_id' => $cook->id,
        ]);

        $this->actingAs($owner)->patch("/meals/{$meal->id}", [
            'recipe_id' => $recipe->id,
            'planned_on' => $meal->planned_on->addDay()->toDateString(),
            'slot' => $meal->slot->value,
            'cook_id' => $cook->id,
        ]);

        Notification::assertSentTo($cook, MealEditedNotification::class);
        Notification::assertNotSentTo($cook, MealAssignedNotification::class);
    }

    #[Test]
    public function deletingAMealAssignedToSomeoneElseNotifiesThem(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $cook = User::factory()->create(['household_id' => $owner->household_id]);
        $recipe = Recipe::factory()->create(['household_id' => $owner->household_id]);
        $meal = PlannedMeal::factory()->create([
            'household_id' => $owner->household_id,
            'recipe_id' => $recipe->id,
            'cook_id' => $cook->id,
        ]);

        $this->actingAs($owner)->delete("/meals/{$meal->id}");

        Notification::assertSentTo($cook, MealDeletedNotification::class);
        Notification::assertNotSentTo($owner, MealDeletedNotification::class);
    }

    #[Test]
    public function deletingYourOwnMealDoesNotNotifyYou(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $recipe = Recipe::factory()->create(['household_id' => $owner->household_id]);
        $meal = PlannedMeal::factory()->create([
            'household_id' => $owner->household_id,
            'recipe_id' => $recipe->id,
            'cook_id' => $owner->id,
        ]);

        $this->actingAs($owner)->delete("/meals/{$meal->id}");

        Notification::assertNothingSent();
    }
}
