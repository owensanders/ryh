<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;
use App\UseCases\House\CancelHouseholdSubscriptionUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HouseholdSettingsTest extends TestCase
{
    use RefreshDatabase;

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
    public function deletingAHouseholdByLeavingCancelsItsSubscription(): void
    {
        $this->mock(CancelHouseholdSubscriptionUseCase::class)
            ->shouldReceive('execute')->once();

        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);

        $this->actingAs($owner)
            ->delete('/settings/households/current', ['household_id' => $owner->household_id]);

        $this->assertDatabaseMissing('households', ['id' => $owner->household_id]);
    }
}
