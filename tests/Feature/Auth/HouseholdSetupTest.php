<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Mail\HouseholdInviteMail;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HouseholdSetupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function aHouseholdlessUserCanCreateAHouseholdAndBecomesOwner(): void
    {
        Mail::fake();

        $user = User::factory()->create(['household_id' => null]);

        $response = $this->actingAs($user)->post('/household/setup', [
            'name' => 'The Parker household',
            'size' => '4 people',
            'invites' => [
                ['email' => 'james@parkerhouse.co.uk', 'role' => 'adult'],
            ],
        ]);

        $response->assertRedirect(route('household.done', absolute: false));

        $user->refresh();
        $this->assertNotNull($user->household_id);
        $this->assertSame(HouseholdRoleEnum::Owner, $user->currentRole());

        $household = $user->household;
        $this->assertSame('The Parker household', $household->name);
        $this->assertNotNull($household->join_code);
        $this->assertTrue($household->isOnTrial());

        Mail::assertQueued(HouseholdInviteMail::class, fn (HouseholdInviteMail $mail) => ! $mail->hasAccount
            && $mail->household->id === $household->id);
    }

    #[Test]
    public function everyHouseholdGetsAJoinCodeRegardlessOfHowItWasCreated(): void
    {
        $household = Household::factory()->create();

        $this->assertNotNull($household->join_code);
    }

    #[Test]
    public function aHouseholdlessUserCanJoinAHouseholdWithAValidCode(): void
    {
        $household = Household::factory()->create(['join_code' => 'PARKER-4Q72KD']);
        $user = User::factory()->create(['household_id' => null]);

        $response = $this->actingAs($user)->post('/household/join', [
            'code' => 'parker-4q72kd',
        ]);

        $response->assertRedirect(route('household.done', absolute: false));

        $user->refresh();
        $this->assertSame($household->id, $user->household_id);
        $this->assertSame(HouseholdRoleEnum::Adult, $user->currentRole());
        $this->assertTrue($user->currentMembership()->pending);
        $this->assertSame(PendingReasonEnum::Requested, $user->currentMembership()->pending_reason);
    }

    #[Test]
    public function joiningFailsWhenTheHouseholdHasTurnedTheCodeOff(): void
    {
        $household = Household::factory()->create(['join_code' => 'PARKER-4Q72KD', 'join_code_enabled' => false]);
        $user = User::factory()->create(['household_id' => null]);

        $response = $this->actingAs($user)->post('/household/join', [
            'code' => 'parker-4q72kd',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->refresh()->household_id);
    }

    #[Test]
    public function joiningWithAnUnknownCodeFailsValidation(): void
    {
        $user = User::factory()->create(['household_id' => null]);

        $response = $this->actingAs($user)->post('/household/join', [
            'code' => 'NOT-A-REAL-CODE',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->refresh()->household_id);
    }

    #[Test]
    public function aNonPendingHousedUserCanStillReachSetupToAddAnotherHousehold(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/household/setup')->assertOk();
    }

    #[Test]
    public function aHouseholdlessUserIsRedirectedToSetupInsteadOfSeeingTheDashboard(): void
    {
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('household.setup', absolute: false));
    }

    #[Test]
    public function aPendingMemberIsRedirectedAwayFromTheDashboardUntilApproved(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $household->id, 'pending' => true]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('household.done', absolute: false));
    }

    #[Test]
    public function aPendingMemberVisitingSetupIsSentToTheWaitingScreenNotDashboard(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $household->id, 'pending' => true]);

        $this->actingAs($user)->get('/household/setup')->assertRedirect(route('household.done', absolute: false));
    }

    #[Test]
    public function thePendingMemberSeesJoinCopyOnTheWaitingScreenEvenWithoutFreshFlashData(): void
    {
        $household = Household::factory()->create(['name' => 'The Parker household']);
        $user = User::factory()->create(['household_id' => $household->id, 'pending' => true]);

        $this->actingAs($user)
            ->get('/household/done')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/HouseholdDone')
                ->where('mode', 'join')
                ->where('householdName', 'The Parker household')
                ->where('pending', true));
    }

    #[Test]
    public function theOwnerIsNotMarkedPendingOnTheDoneScreen(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner, 'pending' => false]);

        $this->actingAs($user)
            ->get('/household/done')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/HouseholdDone')
                ->where('pending', false));
    }

    #[Test]
    public function aUserOnlyGetsOneFreeTrialAcrossHouseholds(): void
    {
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)->post('/household/setup', ['name' => 'First house']);
        $user->refresh();
        $this->assertTrue($user->household->isOnTrial());
        $this->assertNotNull($user->trial_claimed_at);

        $this->actingAs($user)
            ->get('/household/setup')
            ->assertInertia(fn ($page) => $page->where('trialAvailable', false));

        $this->actingAs($user)->post('/household/setup', ['name' => 'Second house']);
        $user->refresh();
        $this->assertSame('Second house', $user->household->name);
        $this->assertFalse($user->household->isOnTrial());
        $this->assertTrue($user->household->needsToSubscribe());
    }
}
