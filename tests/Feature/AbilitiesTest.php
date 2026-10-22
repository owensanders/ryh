<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChoreStatusEnum;
use App\Enums\HouseholdRoleEnum;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Pages show and hide controls from the `can` the policies send, never from the role. */
class AbilitiesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{HouseholdRoleEnum, array<string, bool>}> */
    public static function householdAbilities(): array
    {
        $all = [
            'manageHousehold' => true,
            'viewBudget' => true,
            'viewDocuments' => true,
            'createChores' => true,
            'notifyChores' => true,
            'createShoppingLists' => true,
            'createShoppingItems' => true,
            'createCalendarEvents' => true,
            'createPlannedMeals' => true,
            'createRecipes' => true,
        ];
        $none = array_map(fn () => false, $all);

        return [
            'owner' => [HouseholdRoleEnum::Owner, $all],
            'adult' => [HouseholdRoleEnum::Adult, ['manageHousehold' => false] + $all],
            'teen' => [HouseholdRoleEnum::Teen, ['createCalendarEvents' => true] + $none],
            'child' => [HouseholdRoleEnum::Child, $none],
        ];
    }

    /** @param  array<string, bool>  $expected */
    #[Test]
    #[DataProvider('householdAbilities')]
    public function everyPageIsSentWhatTheirRoleMayDoInTheHousehold(HouseholdRoleEnum $role, array $expected): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get($this->hh('/dashboard'))
            ->assertInertia(fn ($page) => $page->where('household.can', $expected));
    }

    #[Test]
    public function theSettingsNavIsAskedAboutTheHouseholdTheyWereLastIn(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $child = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Child]);

        $this->actingAs($owner)->get('/settings/profile')
            ->assertInertia(fn ($page) => $page
                ->where('household.can.manageHousehold', true)
                ->where('household.can.viewBudget', true)
                ->where('household.can.viewDocuments', true)
                // Creating is only for the household in the URL.
                ->where('household.can.createChores', false));

        $this->actingAs($child)->get('/settings/profile')
            ->assertInertia(fn ($page) => $page
                ->where('household.can.manageHousehold', false)
                ->where('household.can.viewBudget', false));
    }

    #[Test]
    public function aHouseholdOutsideTheUrlGrantsNothingToAPendingMemberOrAStranger(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $household = $owner->lastHousehold;
        $pending = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner, 'pending' => true]);
        $stranger = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->assertTrue(Gate::forUser($owner)->allows('manage', $household));
        $this->assertFalse(Gate::forUser($pending)->allows('manage', $household));
        $this->assertFalse(Gate::forUser($stranger)->allows('manage', $household));
    }

    #[Test]
    public function aTeenMayTickOffOnlyTheirOwnChoresAndEditNone(): void
    {
        $teen = User::factory()->create(['role' => HouseholdRoleEnum::Teen]);
        Chore::factory()->create(['household_id' => $teen->household_id, 'assigned_to' => $teen->id]);
        // In the Done column, so each column holds one chore.
        Chore::factory()->create(['household_id' => $teen->household_id, 'status' => ChoreStatusEnum::Done]);

        $this->actingAs($teen)->get($this->hh('/chores'))
            ->assertInertia(fn ($page) => $page
                ->where('columns.0.items.0.can', ['update' => false, 'delete' => false, 'toggle' => true])
                ->where('columns.1.items.0.can', ['update' => false, 'delete' => false, 'toggle' => false]));
    }

    #[Test]
    public function anAdultMayDoAnythingToAnyChore(): void
    {
        $adult = User::factory()->create(['role' => HouseholdRoleEnum::Adult]);
        Chore::factory()->create(['household_id' => $adult->household_id]);

        $this->actingAs($adult)->get($this->hh('/chores'))
            ->assertInertia(fn ($page) => $page->where('columns.0.items.0.can', ['update' => true, 'delete' => true, 'toggle' => true]));
    }

    #[Test]
    public function aTeenMayTickOffShoppingButAChildMayNot(): void
    {
        $teen = User::factory()->create(['role' => HouseholdRoleEnum::Teen]);
        $child = User::factory()->create(['household_id' => $teen->household_id, 'role' => HouseholdRoleEnum::Child]);
        $list = ShoppingList::factory()->create(['household_id' => $teen->household_id, 'slug' => 'tesco']);
        ShoppingItem::factory()->create(['shopping_list_id' => $list->id]);

        $this->actingAs($teen)->get($this->hh('/shopping/tesco'))
            ->assertInertia(fn ($page) => $page
                ->where('active.can', ['update' => false, 'delete' => false])
                ->where('active.items.0.can', ['update' => false, 'delete' => false, 'toggle' => true]));

        $this->actingAs($child)->get($this->hh('/shopping/tesco'))
            ->assertInertia(fn ($page) => $page->where('active.items.0.can.toggle', false));
    }

    #[Test]
    public function aTeenMayEditTheCalendarButAChildMayNot(): void
    {
        $teen = User::factory()->create(['role' => HouseholdRoleEnum::Teen]);
        $child = User::factory()->create(['household_id' => $teen->household_id, 'role' => HouseholdRoleEnum::Child]);
        // June 2026 starts on a Monday, so the 1st is the grid's first day.
        CalendarEvent::factory()->create([
            'household_id' => $teen->household_id,
            'starts_at' => CarbonImmutable::parse('2026-06-01 18:00'),
            'ends_at' => CarbonImmutable::parse('2026-06-01 19:00'),
        ]);

        $this->actingAs($teen)->get($this->hh('/calendar?month=2026-06'))
            ->assertInertia(fn ($page) => $page->where('calendar.days.0.events.0.can', ['update' => true, 'delete' => true]));

        $this->actingAs($child)->get($this->hh('/calendar?month=2026-06'))
            ->assertInertia(fn ($page) => $page->where('calendar.days.0.events.0.can', ['update' => false, 'delete' => false]));
    }
}
