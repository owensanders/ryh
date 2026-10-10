<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakesStripe;
use Tests\TestCase;

class HouseholdSettingsTest extends TestCase
{
    use FakesStripe;
    use RefreshDatabase;

    #[Test]
    public function itListsEveryHouseholdTheUserBelongsTo(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create(['name' => 'The Cottage']);
        $user->households()->attach($second->id, ['role' => HouseholdRole::Adult, 'pending' => false]);

        $this->actingAs($user)
            ->get('/settings/households')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Households')
                ->where('currentHouseholdId', $user->current_household_id)
                ->has('households', 2)
                ->where('households.1.name', 'The Cottage'));
    }

    #[Test]
    public function itSwitchesToAHouseholdTheUserBelongsTo(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRole::Adult, 'pending' => false]);

        $this->actingAs($user)
            ->patch('/settings/households/current', ['household_id' => $second->id])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'current_household_id' => $second->id]);
    }

    #[Test]
    public function itRefusesToSwitchToAHouseholdTheUserDoesNotBelongTo(): void
    {
        $user = User::factory()->create();
        $stranger = Household::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/households/current', ['household_id' => $stranger->id])
            ->assertSessionHasErrors('household_id');
    }

    #[Test]
    public function aNonSoleOwnerLeavingJustDetaches(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($member)
            ->delete('/settings/households/current', ['household_id' => $owner->household_id])
            ->assertRedirect();

        $this->assertDatabaseMissing('household_user', ['household_id' => $owner->household_id, 'user_id' => $member->id]);
        $this->assertDatabaseHas('households', ['id' => $owner->household_id]);
        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }

    #[Test]
    public function aSoleOwnerWithOtherMembersMustConfirmBeforeLeavingDeletesTheHousehold(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($owner)
            ->delete('/settings/households/current', ['household_id' => $owner->household_id])
            ->assertSessionHasErrors('household');

        $this->assertDatabaseHas('households', ['id' => $owner->household_id]);

        $this->actingAs($owner)
            ->delete('/settings/households/current', [
                'household_id' => $owner->household_id,
                'confirm_household_deletion' => true,
            ])
            ->assertRedirect(route('household.setup', absolute: false));

        $this->assertDatabaseMissing('households', ['id' => $owner->household_id]);
    }

    #[Test]
    public function leavingYourOnlyHouseholdRedirectsToSetup(): void
    {
        $user = User::factory()->create(['role' => HouseholdRole::Adult]);
        User::factory()->create(['household_id' => $user->household_id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)
            ->delete('/settings/households/current', ['household_id' => $user->household_id])
            ->assertRedirect(route('household.setup', absolute: false));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'current_household_id' => null]);
    }

    #[Test]
    public function leavingYourCurrentHouseholdSwitchesToARemainingOne(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRole::Adult, 'pending' => false]);
        $firstHouseholdId = $user->current_household_id;

        $this->actingAs($user)
            ->delete('/settings/households/current', ['household_id' => $firstHouseholdId])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'current_household_id' => $second->id]);
    }

    #[Test]
    public function leavingAPaidHouseholdCancelsItInStripeBeforeDeletingIt(): void
    {
        $stripe = $this->fakeStripe()->respond('DELETE', '/v1/subscriptions/sub_live', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'canceled'));
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $owner->household->update(['stripe_id' => 'cus_test123']);
        Subscription::create([
            'household_id' => $owner->household_id,
            'type' => 'default',
            'stripe_id' => 'sub_live',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
        ]);

        $this->actingAs($owner)->delete('/settings/households/current', ['household_id' => $owner->household_id]);

        $stripe->assertRequested('DELETE', '/v1/subscriptions/sub_live');
        $this->assertDatabaseMissing('households', ['id' => $owner->household_id]);
    }

    #[Test]
    public function aStripeOutageKeepsThePaidHouseholdRatherThanOrphanTheSubscription(): void
    {
        $this->fakeStripe()->respond('DELETE', '/v1/subscriptions/sub_live', ['error' => ['type' => 'api_error', 'message' => 'Down']], 500);
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $owner->household->update(['stripe_id' => 'cus_test123']);
        Subscription::create([
            'household_id' => $owner->household_id,
            'type' => 'default',
            'stripe_id' => 'sub_live',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
        ]);

        $this->actingAs($owner)->delete('/settings/households/current', ['household_id' => $owner->household_id]);

        $this->assertDatabaseHas('households', ['id' => $owner->household_id]);
    }
}
