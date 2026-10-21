<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Removing someone (or cancelling their invite) takes them out of that one
 * household. It used to delete their whole account — so any owner could wipe
 * out anyone's account by inviting their email and cancelling.
 */
class RemoveMemberKeepsAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cancellingAnInviteToSomeoneElsesAccountLeavesTheirAccountAndHouseholdAlone(): void
    {
        $theirHousehold = Household::factory()->create();
        $victim = User::factory()->create(['household_id' => $theirHousehold->id, 'role' => HouseholdRoleEnum::Owner]);

        $stranger = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $this->actingAs($stranger)->post($this->hh('/house/invite'), ['email' => $victim->email, 'role' => 'adult']);
        $this->assertDatabaseHas('household_user', ['household_id' => $stranger->household_id, 'user_id' => $victim->id, 'pending' => true]);

        $this->actingAs($stranger)
            ->delete($this->hh("/house/members/{$victim->id}"))
            ->assertSessionHas('toast', "{$victim->name} — invite cancelled");

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
        $this->assertDatabaseMissing('household_user', ['household_id' => $stranger->household_id, 'user_id' => $victim->id]);
        $this->assertDatabaseHas('household_user', ['household_id' => $theirHousehold->id, 'user_id' => $victim->id, 'role' => 'owner']);
        $this->assertSame($theirHousehold->id, $victim->refresh()->last_household_id);
    }

    #[Test]
    public function aRemovedMemberCanStillSignInToTheirOtherHousehold(): void
    {
        [$owner, $member, $otherHousehold] = $this->memberOfTwoHouseholds();

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"))->assertRedirect();

        $this->post('/logout');
        $this->post('/login', ['email' => $member->email, 'password' => 'password'])
            ->assertRedirect(route('app', absolute: false));
        $this->get('/app')->assertRedirect($this->hh('/dashboard', $otherHousehold));
        $this->assertSame($otherHousehold->id, $member->refresh()->last_household_id);
    }

    #[Test]
    public function removingSomeoneWhoWasViewingThatHouseholdMovesThemToTheirOtherOne(): void
    {
        [$owner, $member, $otherHousehold] = $this->memberOfTwoHouseholds();
        $member->forceFill(['last_household_id' => $owner->household_id])->save();

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"));

        $this->assertSame($otherHousehold->id, $member->refresh()->last_household_id);
    }

    #[Test]
    public function removingSomeoneFromTheirOnlyHouseholdLeavesThemWithNone(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"));

        $this->assertNull($member->refresh()->last_household_id);
        // So next time they sign in they're sent to set one up, rather than into ours.
        $this->actingAs($member)->get('/dashboard')->assertRedirect(route('household.setup', absolute: false));
    }

    #[Test]
    public function aRemovedMemberLosesAccessToTheHousehold(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"));

        // Their old household's pages don't exist for them now.
        $this->actingAs($member->refresh())->get($this->hh('/chores', $owner->household_id))->assertNotFound();
        $this->assertFalse($member->households()->where('households.id', $owner->household_id)->exists());
    }

    #[Test]
    public function theirChoresInOtherHouseholdsAreKept(): void
    {
        [$owner, $member, $otherHousehold] = $this->memberOfTwoHouseholds();
        $here = Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $member->id]);
        $there = Chore::factory()->create(['household_id' => $otherHousehold->id, 'assigned_to' => $member->id]);

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"));

        $this->assertDatabaseMissing('chores', ['id' => $here->id]);
        $this->assertDatabaseHas('chores', ['id' => $there->id, 'assigned_to' => $member->id]);
    }

    #[Test]
    public function theirEventsInOtherHouseholdsAreKept(): void
    {
        [$owner, $member, $otherHousehold] = $this->memberOfTwoHouseholds();
        $soloHere = CalendarEvent::factory()->create(['household_id' => $owner->household_id]);
        $soloHere->attendees()->attach($member->id);
        $soloThere = CalendarEvent::factory()->create(['household_id' => $otherHousehold->id]);
        $soloThere->attendees()->attach($member->id);

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"));

        $this->assertDatabaseMissing('calendar_events', ['id' => $soloHere->id]);
        $this->assertDatabaseHas('calendar_event_user', ['calendar_event_id' => $soloThere->id, 'user_id' => $member->id]);
    }

    #[Test]
    public function theyStayTheCookOnOtherHouseholdsMeals(): void
    {
        [$owner, $member, $otherHousehold] = $this->memberOfTwoHouseholds();
        $here = $this->mealCookedBy($member, $owner->lastHousehold);
        $there = $this->mealCookedBy($member, $otherHousehold);

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"));

        $this->assertDatabaseHas('planned_meals', ['id' => $here->id, 'cook_id' => null]);
        $this->assertDatabaseHas('planned_meals', ['id' => $there->id, 'cook_id' => $member->id]);
    }

    #[Test]
    public function aRemovedMemberCanBeInvitedBackLater(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)->delete($this->hh("/house/members/{$member->id}"));
        $this->actingAs($owner)
            ->post($this->hh('/house/invite'), ['email' => $member->email, 'role' => 'adult'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('household_user', [
            'household_id' => $owner->household_id,
            'user_id' => $member->id,
            'pending' => true,
            'pending_reason' => PendingReasonEnum::Invited->value,
        ]);
    }

    /** @return array{User, User, Household} the owner, a member of the owner's household, and that member's other household */
    private function memberOfTwoHouseholds(): array
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $otherHousehold = Household::factory()->create();
        $member = User::factory()->create(['household_id' => $otherHousehold->id]);
        $member->households()->attach($owner->household_id, ['role' => HouseholdRoleEnum::Adult, 'pending' => false]);

        return [$owner, $member->refresh(), $otherHousehold];
    }

    private function mealCookedBy(User $cook, Household $household): PlannedMeal
    {
        $recipe = Recipe::factory()->create(['household_id' => $household->id]);

        return PlannedMeal::factory()->create(['household_id' => $household->id, 'recipe_id' => $recipe->id, 'cook_id' => $cook->id]);
    }
}
