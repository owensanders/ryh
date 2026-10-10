<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Mail\HouseholdInviteMail;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HouseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRendersTheHouseholdAndItsMembers(): void
    {
        $household = Household::create([
            'name' => 'The Parkers', 'location' => 'Bristol', 'streak_days' => 12,
            'trial_ends_at' => now()->addDays(30),
        ]);
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);
        User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Adult]);
        User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Adult, 'pending' => true]);

        $this->actingAs($owner)
            ->get('/house')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('House')
                ->where('house.houseName', 'The Parkers')
                ->where('house.memberCount', 3)
                ->where('house.houseStats.0.value', '2')
                ->where('house.houseStats.1.value', '1')
                ->count('house.members', 3)
                ->count('house.roles', 4)
                ->where('house.planStatus.onTrial', true)
                ->where('house.planStatus.subscribed', false)
            );
    }

    #[Test]
    public function itShowsAnAnnualSubscriptionThatHasBeenCancelled(): void
    {
        config(['plans.intervals.annual.stripe_price' => 'price_home_annual_test']);

        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        Subscription::create([
            'household_id' => $owner->household->id,
            'type' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_home_annual_test',
            'ends_at' => now()->addMonths(3),
        ]);

        $this->actingAs($owner)
            ->get('/house')
            ->assertInertia(fn ($page) => $page
                ->where('house.planStatus.subscribed', true)
                ->where('house.planStatus.interval', 'annual')
                ->where('house.planStatus.endsLabel', now()->addMonths(3)->format('j F Y'))
            );
    }

    #[Test]
    public function itShowsASubscriptionWhenStripePricesAreNotConfigured(): void
    {
        config(['plans.intervals.annual.stripe_price' => null]);

        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        Subscription::create([
            'household_id' => $owner->household->id,
            'type' => 'default',
            'stripe_id' => 'sub_test456',
            'stripe_status' => 'active',
            'stripe_price' => 'price_home_monthly_test',
        ]);

        $this->actingAs($owner)
            ->get('/house')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('house.planStatus.subscribed', true)
                ->where('house.planStatus.interval', 'monthly')
            );
    }

    #[Test]
    public function itRefusesToStartCheckoutWhenBillingIsNotConfigured(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $owner->household->update(['trial_ends_at' => now()->addDays(30)]);

        $this->actingAs($owner)
            ->post('/house/subscribe', ['interval' => 'monthly'])
            ->assertSessionHasErrors('plan');
    }

    #[Test]
    public function itQueuesARegistrationInviteForANewEmail(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'margaret@parkerhouse.co.uk', 'role' => 'adult'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Invite sent to margaret@parkerhouse.co.uk');

        $this->assertDatabaseMissing('users', ['email' => 'margaret@parkerhouse.co.uk']);

        Mail::assertQueued(HouseholdInviteMail::class, fn (HouseholdInviteMail $mail) => ! $mail->hasAccount
            && $mail->household->id === $owner->household_id
            && str_contains($mail->actionUrl, route('register', [], false)));
    }

    #[Test]
    public function itInvitesAnExistingUserToANewHousehold(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $existing = User::factory()->create(['email' => 'margaret@parkerhouse.co.uk']);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'margaret@parkerhouse.co.uk', 'role' => 'adult'])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Invite sent to margaret@parkerhouse.co.uk');

        $this->assertDatabaseHas('household_user', [
            'household_id' => $owner->household_id,
            'user_id' => $existing->id,
            'pending' => true,
            'pending_reason' => 'invited',
        ]);

        Mail::assertQueued(HouseholdInviteMail::class, fn (HouseholdInviteMail $mail) => $mail->hasAccount
            && $mail->household->id === $owner->household_id);
    }

    #[Test]
    public function itRejectsInvitingSomeoneAlreadyInTheHousehold(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => $member->email, 'role' => 'adult'])
            ->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function itApprovesAMemberWhoRequestedToJoin(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $requester = User::factory()->create([
            'household_id' => $owner->household_id,
            'pending' => true,
            'pending_reason' => PendingReasonEnum::Requested,
        ]);

        $this->actingAs($owner)
            ->patch("/house/members/{$requester->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('toast', "{$requester->name} approved — welcome to the household");

        $requester->refresh();
        $this->assertFalse($requester->currentMembership()->pending);
        $this->assertNull($requester->currentMembership()->pending_reason);
    }

    #[Test]
    public function itDoesNotApproveAnotherHouseholdsMember(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $stranger = User::factory()->create(['pending' => true, 'pending_reason' => PendingReasonEnum::Requested]);

        $this->actingAs($owner)->patch("/house/members/{$stranger->id}/approve")->assertNotFound();

        $this->assertTrue($stranger->refresh()->currentMembership()->pending);
    }

    #[Test]
    public function itRejectsAnInviteWithMissingFields(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => '', 'role' => 'adult'])
            ->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function itChangesAMembersRole(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($owner)
            ->patch("/house/members/{$member->id}/role", ['role' => 'teen', 'guardian_confirmed' => true])
            ->assertRedirect()
            ->assertSessionHas('toast', "{$member->name} is now Teen");

        $this->assertSame(HouseholdRoleEnum::Teen, $member->refresh()->currentRole());
    }

    #[Test]
    public function itRefusesToDemoteTheSoleOwner(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->patch("/house/members/{$owner->id}/role", ['role' => 'adult'])
            ->assertSessionHasErrors('role');

        $this->assertSame(HouseholdRoleEnum::Owner, $owner->refresh()->currentRole());
    }

    #[Test]
    public function itAllowsDemotingAnOwnerWhenAnotherOwnerRemains(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->patch("/house/members/{$owner->id}/role", ['role' => 'adult'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(HouseholdRoleEnum::Adult, $owner->refresh()->currentRole());
    }

    #[Test]
    public function itRemovesAMember(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id, 'name' => 'James Parker']);

        $this->actingAs($owner)
            ->delete("/house/members/{$member->id}")
            ->assertRedirect()
            ->assertSessionHas('toast', 'James Parker removed from the household');

        // Out of the household, but their account stays.
        $this->assertDatabaseMissing('household_user', ['household_id' => $owner->household_id, 'user_id' => $member->id]);
        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }

    #[Test]
    public function itRefusesToRemoveTheSoleOwner(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->delete("/house/members/{$owner->id}")
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    #[Test]
    public function itCancelsAPendingInvite(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $invitee = User::factory()->create(['household_id' => $owner->household_id, 'name' => 'Margaret Parker', 'pending' => true]);

        $this->actingAs($owner)
            ->delete("/house/members/{$invitee->id}")
            ->assertRedirect()
            ->assertSessionHas('toast', 'Margaret Parker — invite cancelled');

        $this->assertDatabaseMissing('household_user', ['household_id' => $owner->household_id, 'user_id' => $invitee->id]);
        $this->assertDatabaseHas('users', ['id' => $invitee->id]);
    }

    #[Test]
    public function itDeletesChoresAssignedToTheRemovedMember(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);
        $chore = Chore::factory()->create(['household_id' => $owner->household_id, 'assigned_to' => $member->id]);

        $this->actingAs($owner)->delete("/house/members/{$member->id}")->assertRedirect();

        $this->assertDatabaseMissing('chores', ['id' => $chore->id]);
    }

    #[Test]
    public function itDeletesAnEventWhenTheRemovedMemberWasItsOnlyAttendee(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);
        $event = CalendarEvent::factory()->create(['household_id' => $owner->household_id]);
        $event->attendees()->attach($member->id);

        $this->actingAs($owner)->delete("/house/members/{$member->id}")->assertRedirect();

        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    #[Test]
    public function itOnlyDetachesTheRemovedMemberFromAnEventWithOtherAttendees(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);
        $event = CalendarEvent::factory()->create(['household_id' => $owner->household_id]);
        $event->attendees()->attach([$member->id, $owner->id]);

        $this->actingAs($owner)->delete("/house/members/{$member->id}")->assertRedirect();

        $this->assertDatabaseHas('calendar_events', ['id' => $event->id]);
        $this->assertDatabaseMissing('calendar_event_user', ['calendar_event_id' => $event->id, 'user_id' => $member->id]);
        $this->assertDatabaseHas('calendar_event_user', ['calendar_event_id' => $event->id, 'user_id' => $owner->id]);
    }

    #[Test]
    public function itUnassignsPlannedMealsCookedByTheRemovedMember(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id]);
        $recipe = Recipe::factory()->create(['household_id' => $owner->household_id]);
        $meal = PlannedMeal::factory()->create([
            'household_id' => $owner->household_id, 'cook_id' => $member->id, 'recipe_id' => $recipe->id,
        ]);

        $this->actingAs($owner)->delete("/house/members/{$member->id}")->assertRedirect();

        $this->assertDatabaseHas('planned_meals', ['id' => $meal->id, 'cook_id' => null]);
    }

    #[Test]
    public function itTogglesTheInviteCodeOff(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->patch('/house/join-code', ['enabled' => false])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Invite code turned off');

        $this->assertFalse($owner->household->refresh()->join_code_enabled);
    }

    #[Test]
    public function itTogglesTheInviteCodeBackOn(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $owner->household->update(['join_code_enabled' => false]);

        $this->actingAs($owner)
            ->patch('/house/join-code', ['enabled' => true])
            ->assertRedirect()
            ->assertSessionHas('toast', 'Invite code turned on');

        $this->assertTrue($owner->household->refresh()->join_code_enabled);
    }

    #[Test]
    public function itRegeneratesTheInviteCode(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $originalCode = $owner->household->join_code;

        $this->actingAs($owner)
            ->post('/house/join-code/regenerate')
            ->assertRedirect()
            ->assertSessionHas('toast', 'New invite code generated');

        $this->assertNotSame($originalCode, $owner->household->refresh()->join_code);
    }

    #[Test]
    public function itDoesNotTouchAnotherHouseholdsMembers(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $stranger = User::factory()->create();

        $this->actingAs($owner)->patch("/house/members/{$stranger->id}/role", ['role' => 'teen', 'guardian_confirmed' => true])->assertNotFound();
        $this->actingAs($owner)->delete("/house/members/{$stranger->id}")->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $stranger->id]);
    }
}
