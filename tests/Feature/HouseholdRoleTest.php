<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatusEnum;
use App\Enums\HouseholdRoleEnum;
use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\Household;
use App\Models\IncomeSource;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HouseholdRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function teenAndChildCanViewTheSharedScreens(): void
    {
        foreach ([HouseholdRoleEnum::Teen, HouseholdRoleEnum::Child] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/dashboard')->assertOk();
            $this->actingAs($user)->get('/meals')->assertOk();
            $this->actingAs($user)->get('/recipes')->assertOk();
            $this->actingAs($user)->get('/shopping')->assertOk();
            $this->actingAs($user)->get('/calendar')->assertOk();
            $this->actingAs($user)->get('/chores')->assertOk();
        }
    }

    #[Test]
    public function teenAndChildAreForbiddenFromBudgetAndHouse(): void
    {
        foreach ([HouseholdRoleEnum::Teen, HouseholdRoleEnum::Child] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/budget')->assertForbidden();
            $this->actingAs($user)->get('/house')->assertForbidden();
        }
    }

    #[Test]
    public function teenAndChildCannotCreateEditOrDeleteHouseholdData(): void
    {
        foreach ([HouseholdRoleEnum::Teen, HouseholdRoleEnum::Child] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $household = $user->household;

            $meal = PlannedMeal::factory()->create(['household_id' => $household->id]);
            $recipe = Recipe::factory()->create(['household_id' => $household->id]);
            $list = ShoppingList::factory()->create(['household_id' => $household->id, 'slug' => 'tesco']);
            $item = ShoppingItem::factory()->create(['shopping_list_id' => $list->id]);
            $chore = Chore::factory()->create(['household_id' => $household->id]);
            $category = BudgetCategory::factory()->create(['household_id' => $household->id]);
            $income = IncomeSource::factory()->create(['household_id' => $household->id]);

            $this->actingAs($user)->post('/meals', [])->assertForbidden();
            $this->actingAs($user)->patch("/meals/{$meal->id}", [])->assertForbidden();
            $this->actingAs($user)->delete("/meals/{$meal->id}")->assertForbidden();

            $this->actingAs($user)->post('/recipes', [])->assertForbidden();
            $this->actingAs($user)->patch("/recipes/{$recipe->id}", [])->assertForbidden();
            $this->actingAs($user)->delete("/recipes/{$recipe->id}")->assertForbidden();

            $this->actingAs($user)->post('/shopping/tesco/items', [])->assertForbidden();
            $this->actingAs($user)->patch("/shopping-items/{$item->id}", [])->assertForbidden();
            $this->actingAs($user)->delete("/shopping-items/{$item->id}")->assertForbidden();
            $this->actingAs($user)->post('/shopping-lists', [])->assertForbidden();
            $this->actingAs($user)->patch("/shopping-lists/{$list->id}", [])->assertForbidden();
            $this->actingAs($user)->delete("/shopping-lists/{$list->id}")->assertForbidden();

            $this->actingAs($user)->post('/chores', [])->assertForbidden();
            $this->actingAs($user)->patch("/chores/{$chore->id}", [])->assertForbidden();
            $this->actingAs($user)->delete("/chores/{$chore->id}")->assertForbidden();
            $this->actingAs($user)->patch("/chores/{$chore->id}/move", [])->assertForbidden();

            $this->actingAs($user)->post('/budget/categories', [])->assertForbidden();
            $this->actingAs($user)->patch("/budget/categories/{$category->id}", [])->assertForbidden();
            $this->actingAs($user)->delete("/budget/categories/{$category->id}")->assertForbidden();
            $this->actingAs($user)->post('/budget/income', [])->assertForbidden();
            $this->actingAs($user)->patch("/budget/income/{$income->id}", [])->assertForbidden();
            $this->actingAs($user)->delete("/budget/income/{$income->id}")->assertForbidden();
        }
    }

    #[Test]
    public function childCannotCreateEditOrDeleteCalendarEvents(): void
    {
        $child = User::factory()->create(['role' => HouseholdRoleEnum::Child]);
        $event = CalendarEvent::factory()->create(['household_id' => $child->household_id]);

        $this->actingAs($child)->post('/calendar/events', [])->assertForbidden();
        $this->actingAs($child)->patch("/calendar/events/{$event->id}", [])->assertForbidden();
        $this->actingAs($child)->delete("/calendar/events/{$event->id}")->assertForbidden();
    }

    #[Test]
    public function teenCanCreateEditAndDeleteCalendarEvents(): void
    {
        $teen = User::factory()->create(['role' => HouseholdRoleEnum::Teen]);

        $this->actingAs($teen)
            ->post('/calendar/events', [
                'title' => 'Football practice',
                'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $event = CalendarEvent::query()->firstOrFail();

        $this->actingAs($teen)
            ->patch("/calendar/events/{$event->id}", [
                'title' => 'Football practice (moved)',
                'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->actingAs($teen)->delete("/calendar/events/{$event->id}")->assertRedirect();
    }

    #[Test]
    public function childCanOnlyToggleTheirOwnChore(): void
    {
        $household = Household::factory()->create();
        $child = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Child]);
        $ownChore = Chore::factory()->create(['household_id' => $household->id, 'assigned_to' => $child->id, 'status' => ChoreStatusEnum::Today]);
        $othersChore = Chore::factory()->create(['household_id' => $household->id, 'status' => ChoreStatusEnum::Today]);

        $this->actingAs($child)->patch("/chores/{$othersChore->id}/toggle")->assertForbidden();
        $this->actingAs($child)->patch("/chores/{$ownChore->id}/toggle")->assertRedirect();
    }

    #[Test]
    public function teenCanToggleAnyChoreAssignedToThemButNotOthers(): void
    {
        $household = Household::factory()->create();
        $teen = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Teen]);
        $ownChore = Chore::factory()->create(['household_id' => $household->id, 'assigned_to' => $teen->id, 'status' => ChoreStatusEnum::Today]);
        $othersChore = Chore::factory()->create(['household_id' => $household->id, 'status' => ChoreStatusEnum::Today]);

        $this->actingAs($teen)->patch("/chores/{$othersChore->id}/toggle")->assertForbidden();
        $this->actingAs($teen)->patch("/chores/{$ownChore->id}/toggle")->assertRedirect();
    }

    #[Test]
    public function teenCanToggleShoppingItemsButChildCannot(): void
    {
        $list = ShoppingList::factory()->create(['slug' => 'tesco']);
        $item = ShoppingItem::factory()->create(['shopping_list_id' => $list->id]);

        $teen = User::factory()->create(['household_id' => $list->household_id, 'role' => HouseholdRoleEnum::Teen]);
        $child = User::factory()->create(['household_id' => $list->household_id, 'role' => HouseholdRoleEnum::Child]);

        $this->actingAs($teen)->patch("/shopping-items/{$item->id}/toggle")->assertRedirect();
        $this->actingAs($child)->patch("/shopping-items/{$item->id}/toggle")->assertForbidden();
    }

    #[Test]
    public function ownerAndAdultAreUnrestricted(): void
    {
        foreach ([HouseholdRoleEnum::Owner, HouseholdRoleEnum::Adult] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/budget')->assertOk();

            $chore = Chore::factory()->create(['household_id' => $user->household_id, 'status' => ChoreStatusEnum::Today]);
            $this->actingAs($user)->patch("/chores/{$chore->id}/toggle")->assertRedirect();
        }
    }

    #[Test]
    public function onlyOwnerCanAccessTheHouseTab(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $this->actingAs($owner)->get('/house')->assertOk();

        $adult = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Adult]);
        $this->actingAs($adult)->get('/house')->assertForbidden();
    }

    #[Test]
    public function anAdultCannotReassignOrRemoveTheOwner(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $adult = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($adult)->patch("/house/members/{$owner->id}/role", ['role' => 'adult'])->assertForbidden();
        $this->actingAs($adult)->patch("/house/members/{$adult->id}/role", ['role' => 'owner'])->assertForbidden();
        $this->actingAs($adult)->delete("/house/members/{$owner->id}")->assertForbidden();
        $this->actingAs($adult)->post('/house/invite', ['email' => 'someone@example.com', 'role' => 'adult'])->assertForbidden();

        $this->assertSame(HouseholdRoleEnum::Owner, $owner->refresh()->currentRole());
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }
}
