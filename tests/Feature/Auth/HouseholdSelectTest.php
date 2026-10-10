<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HouseholdSelectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function aUserWithOneHouseholdSkipsThePickerAndReachesTheDashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    #[Test]
    public function aUserWithNoHouseholdIsRedirectedToSetupNotThePicker(): void
    {
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('household.setup', absolute: false));
    }

    #[Test]
    public function aUserWithTwoHouseholdsAndNoCurrentPickIsRedirectedToThePickerFromDashboard(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRoleEnum::Adult, 'pending' => false]);
        $user->forceFill(['current_household_id' => null])->save();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('household.select', absolute: false));
    }

    #[Test]
    public function aUserWithTwoHouseholdsButAnAlreadyPickedCurrentOneSkipsThePicker(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRoleEnum::Adult, 'pending' => false]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    #[Test]
    public function thePickerListsEveryHouseholdTheUserBelongsToWithRoleAndMemberCount(): void
    {
        $user = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRoleEnum::Adult, 'pending' => false]);
        $user->forceFill(['current_household_id' => null])->save();

        $this->actingAs($user)
            ->get('/household/select')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/HouseholdSelect')
                ->count('households', 2));
    }

    #[Test]
    public function selectingAHouseholdPersistsCurrentHouseholdIdAndRedirectsToDashboard(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRoleEnum::Adult, 'pending' => false]);

        $this->actingAs($user)
            ->post('/household/select', ['household_id' => $second->id])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'current_household_id' => $second->id]);
    }

    #[Test]
    public function aUserCannotSelectAHouseholdTheyDoNotBelongTo(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRoleEnum::Adult, 'pending' => false]);
        $stranger = Household::factory()->create();

        $this->actingAs($user)
            ->post('/household/select', ['household_id' => $stranger->id])
            ->assertSessionHasErrors('household_id');
    }

    #[Test]
    public function aUserWithAStaleCurrentHouseholdIsSentBackToThePicker(): void
    {
        $user = User::factory()->create();
        $second = Household::factory()->create();
        $user->households()->attach($second->id, ['role' => HouseholdRoleEnum::Adult, 'pending' => false]);
        // A household the user isn't (or is no longer) a member of — e.g. they
        // were removed from it after it became their current pick.
        $noLongerAMember = Household::factory()->create();
        $user->forceFill(['current_household_id' => $noLongerAMember->id])->save();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('household.select', absolute: false));
    }
}
