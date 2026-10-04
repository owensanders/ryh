<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\HouseholdRole;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Laravel\Cashier\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakesStripe;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use FakesStripe;
    use RefreshDatabase;

    #[Test]
    public function itDeletesAUserWithNoHousehold(): void
    {
        $user = User::factory()->create(['household_id' => null, 'role' => HouseholdRole::Adult]);

        $this->actingAs($user)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function itDeletesANonOwnerMemberWithoutTouchingTheHousehold(): void
    {
        $household = Household::factory()->create();
        User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);
        $member = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Adult]);

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
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);
        User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

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
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

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
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);
        $member = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Adult]);

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
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);
        $member = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Adult]);
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

        $this->assertDatabaseHas('users', ['id' => $member->id, 'current_household_id' => null]);
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
        $member = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($member)->delete('/settings/profile', ['password' => 'password']);

        $stripe->assertNothingRequested();
        $this->assertTrue($owner->household->fresh()->subscribed('default'));
    }

    private function ownerOfPaidHousehold(): User
    {
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $owner->household->update(['stripe_id' => 'cus_test123']);
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
