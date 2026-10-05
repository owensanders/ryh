<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Enums\PendingReason;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HouseholdInviteAcceptTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function acceptingAnInviteActivatesTheMembershipAndSendsThemToThePicker(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create();
        $user->households()->attach($household->id, [
            'role' => HouseholdRole::Adult,
            'pending' => true,
            'pending_reason' => PendingReason::Invited,
        ]);
        $originalHouseholdId = $user->current_household_id;

        $url = URL::temporarySignedRoute('household-invites.accept', now()->addDays(7), [
            'household' => $household->id,
            'member' => $user->id,
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('household.select', absolute: false))
            ->assertSessionHas('toast', "You're in {$household->name} — pick a household to view");

        $this->assertDatabaseHas('household_user', [
            'household_id' => $household->id,
            'user_id' => $user->id,
            'pending' => false,
            'pending_reason' => null,
        ]);
        $this->assertSame($originalHouseholdId, $user->refresh()->current_household_id);
    }

    #[Test]
    public function aUserCannotAcceptAnInviteMeantForSomeoneElse(): void
    {
        $household = Household::factory()->create();
        $invitee = User::factory()->create();
        $invitee->households()->attach($household->id, [
            'role' => HouseholdRole::Adult,
            'pending' => true,
            'pending_reason' => PendingReason::Invited,
        ]);
        $stranger = User::factory()->create();

        $url = URL::temporarySignedRoute('household-invites.accept', now()->addDays(7), [
            'household' => $household->id,
            'member' => $invitee->id,
        ]);

        $this->actingAs($stranger)->get($url)->assertForbidden();
    }

    #[Test]
    public function aGuestIsSentToLoginThenBackToTheAcceptLink(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create();
        $user->households()->attach($household->id, [
            'role' => HouseholdRole::Adult,
            'pending' => true,
            'pending_reason' => PendingReason::Invited,
        ]);

        $url = URL::temporarySignedRoute('household-invites.accept', now()->addDays(7), [
            'household' => $household->id,
            'member' => $user->id,
        ]);

        $this->get($url)->assertRedirect(route('login', absolute: false));

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect($url);
    }

    #[Test]
    public function aTamperedInviteLinkIsRejected(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create();
        $user->households()->attach($household->id, [
            'role' => HouseholdRole::Adult,
            'pending' => true,
            'pending_reason' => PendingReason::Invited,
        ]);

        $url = URL::temporarySignedRoute('household-invites.accept', now()->addDays(7), [
            'household' => $household->id,
            'member' => $user->id,
        ]);

        $tampered = str_replace("member={$user->id}", 'member='.($user->id + 1), $url);

        $this->actingAs($user)->get($tampered)->assertForbidden();
    }

    #[Test]
    public function pickingTheAcceptedHouseholdFromThePickerReachesTheDashboard(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->create();
        $user->households()->attach($household->id, [
            'role' => HouseholdRole::Adult,
            'pending' => true,
            'pending_reason' => PendingReason::Invited,
        ]);

        $url = URL::temporarySignedRoute('household-invites.accept', now()->addDays(7), [
            'household' => $household->id,
            'member' => $user->id,
        ]);
        $this->actingAs($user)->get($url);

        $this->actingAs($user)
            ->post('/household/select', ['household_id' => $household->id])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($user->refresh())->get('/dashboard')->assertOk();
    }
}
