<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\HouseholdRoleEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakesStripeTrait;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use FakesStripeTrait;
    use RefreshDatabase;

    #[Test]
    public function itDeletesAUserWithNoHousehold(): void
    {
        $user = User::factory()->create(['household_id' => null, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($user)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function itDeletesEveryHouseholdTheyAreTheOnlyOwnerOfNotJustTheLastOne(): void
    {
        $last = Household::factory()->create();
        $other = Household::factory()->create();
        $shared = Household::factory()->create();
        $owner = User::factory()->create(['household_id' => $last->id, 'role' => HouseholdRoleEnum::Owner]);
        $owner->households()->attach($other->id, ['role' => HouseholdRoleEnum::Owner, 'pending' => false]);
        $owner->households()->attach($shared->id, ['role' => HouseholdRoleEnum::Owner, 'pending' => false]);
        User::factory()->create(['household_id' => $shared->id, 'role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('households', ['id' => $last->id]);
        $this->assertDatabaseMissing('households', ['id' => $other->id]);
        // Someone else still owns this one.
        $this->assertDatabaseHas('households', ['id' => $shared->id]);
    }

    #[Test]
    public function itAsksBeforeDeletingAnyOfTheirHouseholdsOthersStillUse(): void
    {
        $last = Household::factory()->create();
        $other = Household::factory()->create(['name' => 'The Cottage']);
        $owner = User::factory()->create(['household_id' => $last->id, 'role' => HouseholdRoleEnum::Owner]);
        $owner->households()->attach($other->id, ['role' => HouseholdRoleEnum::Owner, 'pending' => false]);
        User::factory()->create(['household_id' => $other->id, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($owner)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertSessionHasErrors('household');

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('households', ['id' => $other->id]);
    }

    #[Test]
    public function itDeletesANonOwnerMemberWithoutTouchingTheHousehold(): void
    {
        $household = Household::factory()->create();
        User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($member)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
        $this->assertDatabaseHas('households', ['id' => $household->id]);
    }

    #[Test]
    public function itDeletesAnOwnerWhenAnotherOwnerRemains(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);
        User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('households', ['id' => $household->id]);
    }

    #[Test]
    public function itDeletesTheHouseholdWhenTheSoleOwnerHasNoOtherMembers(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);

        $this->actingAs($owner)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('households', ['id' => $household->id]);
    }

    #[Test]
    public function itBlocksTheSoleOwnerWithOtherMembersWithoutConfirmation(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($owner)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertSessionHasErrors('household');

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('users', ['id' => $member->id]);
        $this->assertDatabaseHas('households', ['id' => $household->id]);
    }

    #[Test]
    public function itDeletesTheHouseholdAndUnlinksOtherMembersWhenConfirmed(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);
        $member = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Adult]);
        $chore = Chore::factory()->create(['household_id' => $household->id]);

        $this->actingAs($owner)
            ->delete('/settings/profile', [
                'password' => 'password',
                'confirm_household_deletion' => true,
            ])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('households', ['id' => $household->id]);
        $this->assertDatabaseMissing('chores', ['id' => $chore->id]);

        $this->assertDatabaseHas('users', ['id' => $member->id, 'last_household_id' => null]);
        $this->assertDatabaseMissing('household_user', ['user_id' => $member->id, 'household_id' => $household->id]);
    }

    #[Test]
    public function deletingTheSoleOwnersAccountCancelsTheHouseholdsSubscription(): void
    {
        $stripe = $this->fakeStripe()->respond('DELETE', '/v1/subscriptions/sub_live', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'canceled'));
        $owner = $this->ownerOfPaidHousehold();
        $householdId = $owner->household_id;

        $this->actingAs($owner)->delete('/settings/profile', ['password' => 'password']);

        $stripe->assertRequested('DELETE', '/v1/subscriptions/sub_live');
        $this->assertDatabaseMissing('households', ['id' => $householdId]);
    }

    #[Test]
    public function deletingANonOwnerLeavesTheSubscriptionAlone(): void
    {
        $stripe = $this->fakeStripe();
        $owner = $this->ownerOfPaidHousehold();
        $member = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($member)->delete('/settings/profile', ['password' => 'password']);

        $stripe->assertNothingRequested();
        $this->assertTrue($owner->lastHousehold->fresh()->subscribed('default'));
    }

    private function ownerOfPaidHousehold(): User
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $owner->lastHousehold->update(['stripe_id' => 'cus_test123']);
        Subscription::create([
            'household_id' => $owner->household_id,
            'type' => 'default',
            'stripe_id' => 'sub_live',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
        ]);

        return $owner;
    }
}
