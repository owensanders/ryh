<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Mail\HouseholdInviteMail;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Terms of Service let under-18s join only as a Teen or Child, with the
 * adult adding them confirming they're the parent or guardian — and we record it.
 */
class GuardianConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function membership(User $member, int $householdId): HouseholdUser
    {
        /** @var HouseholdUser */
        return $member->households()->where('households.id', $householdId)->firstOrFail()->pivot;
    }

    #[Test]
    public function invitingATeenOrChildNeedsTheGuardianConfirmation(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);

        foreach (['teen', 'child'] as $role) {
            $this->actingAs($owner)
                ->post('/house/invite', ['email' => "kid-{$role}@parkerhouse.co.uk", 'role' => $role])
                ->assertSessionHasErrors('guardian_confirmed');
        }

        Mail::assertNothingQueued();
    }

    #[Test]
    public function invitingAnAdultDoesNotAskForIt(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $adult = User::factory()->create(['household_id' => null]);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => $adult->email, 'role' => 'adult'])
            ->assertSessionHasNoErrors();

        $membership = $this->membership($adult, $owner->household_id);
        $this->assertNull($membership->guardian_confirmed_by);
        $this->assertNull($membership->guardian_confirmed_at);
    }

    #[Test]
    public function invitingAnExistingAccountAsAChildRecordsWhoConfirmedAndWhen(): void
    {
        Mail::fake();
        $this->freezeSecond();
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $child = User::factory()->create(['household_id' => null]);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => $child->email, 'role' => 'child', 'guardian_confirmed' => true])
            ->assertSessionHasNoErrors();

        $membership = $this->membership($child, $owner->household_id);
        $this->assertSame(HouseholdRole::Child, $membership->role);
        $this->assertSame($owner->id, $membership->guardian_confirmed_by);
        $this->assertTrue($membership->guardian_confirmed_at?->equalTo(now()));
    }

    #[Test]
    public function aNewChildInvitedByEmailJoinsWithTheConfirmationFromTheirInvite(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $invitedAt = now()->startOfSecond();
        $this->travelTo($invitedAt);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'mia@parkerhouse.co.uk', 'role' => 'child', 'guardian_confirmed' => true])
            ->assertSessionHasNoErrors();

        $actionUrl = null;
        Mail::assertQueued(HouseholdInviteMail::class, function (HouseholdInviteMail $mail) use (&$actionUrl): bool {
            $actionUrl = $mail->actionUrl;

            return true;
        });

        // The child opens the link a day later and registers.
        $this->travel(1)->days();
        auth()->logout();
        $this->get($actionUrl)->assertOk();
        $this->post('/register', [
            'name' => 'Mia Parker',
            'email' => 'mia@parkerhouse.co.uk',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ])->assertRedirect(route('dashboard', absolute: false));

        $mia = User::where('email', 'mia@parkerhouse.co.uk')->firstOrFail();
        $membership = $this->membership($mia, $owner->household_id);
        $this->assertSame(HouseholdRole::Child, $membership->role);
        $this->assertSame($owner->id, $membership->guardian_confirmed_by);
        $this->assertTrue($membership->guardian_confirmed_at?->equalTo($invitedAt));
    }

    #[Test]
    public function aChildInviteLinkFromBeforeConfirmationsCannotAddThem(): void
    {
        $household = Household::factory()->create(['name' => 'The Parkers']);

        $legacyUrl = URL::temporarySignedRoute('register', now()->addDays(7), [
            'invite_household' => $household->id,
            'invite_role' => 'child',
            'email' => 'noah@parkerhouse.co.uk',
        ]);

        $this->get($legacyUrl)->assertOk();
        $this->post('/register', [
            'name' => 'Noah Parker',
            'email' => 'noah@parkerhouse.co.uk',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ])
            ->assertRedirect(route('household.setup', absolute: false))
            ->assertSessionHas('reinviteHousehold', 'The Parkers');

        $this->assertNull(User::where('email', 'noah@parkerhouse.co.uk')->firstOrFail()->current_household_id);
    }

    #[Test]
    public function makingSomeoneATeenOrChildNeedsTheConfirmation(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($owner)
            ->patch("/house/members/{$member->id}/role", ['role' => 'child'])
            ->assertSessionHasErrors('guardian_confirmed');

        $this->assertSame(HouseholdRole::Adult, $member->refresh()->currentRole());
    }

    #[Test]
    public function aConfirmedRoleChangeIsRecordedAndClearedWhenTheyBecomeAnAdult(): void
    {
        $owner = User::factory()->create(['role' => HouseholdRole::Owner]);
        $member = User::factory()->create(['household_id' => $owner->household_id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($owner)
            ->patch("/house/members/{$member->id}/role", ['role' => 'teen', 'guardian_confirmed' => true])
            ->assertSessionHasNoErrors();

        $this->assertSame($owner->id, $this->membership($member, $owner->household_id)->guardian_confirmed_by);

        // Turning 18: back to Adult, and the guardian record goes with the role.
        $this->actingAs($owner)
            ->patch("/house/members/{$member->id}/role", ['role' => 'adult'])
            ->assertSessionHasNoErrors();

        $membership = $this->membership($member, $owner->household_id);
        $this->assertNull($membership->guardian_confirmed_by);
        $this->assertNull($membership->guardian_confirmed_at);
    }

    #[Test]
    public function theSetupWizardNeedsTheConfirmationOnlyWhenItInvitesATeenOrChild(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['household_id' => null]);
        $invites = [
            ['email' => 'james@parkerhouse.co.uk', 'role' => 'adult'],
            ['email' => 'noah@parkerhouse.co.uk', 'role' => 'child'],
        ];

        $this->actingAs($owner)
            ->post('/household/setup', ['name' => 'The Parkers', 'invites' => $invites])
            ->assertSessionHasErrors('guardian_confirmed');
        $this->assertNull($owner->refresh()->current_household_id);

        $this->actingAs($owner)
            ->post('/household/setup', ['name' => 'The Parkers', 'invites' => $invites, 'guardian_confirmed' => true])
            ->assertRedirect(route('household.done', absolute: false));

        Mail::assertQueued(HouseholdInviteMail::class, fn (HouseholdInviteMail $mail) => str_contains($mail->actionUrl, "invite_guardian={$owner->id}"));
    }

    #[Test]
    public function theSetupWizardDoesNotAskWhenEveryoneIsAnAdult(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['household_id' => null]);

        $this->actingAs($owner)
            ->post('/household/setup', ['name' => 'The Parkers', 'invites' => [['email' => 'james@parkerhouse.co.uk', 'role' => 'adult']]])
            ->assertRedirect(route('household.done', absolute: false));

        Mail::assertQueued(HouseholdInviteMail::class, fn (HouseholdInviteMail $mail) => ! str_contains($mail->actionUrl, 'invite_guardian'));
    }

    #[Test]
    public function aTeenOrChildMembershipCannotBeWrittenWithoutAGuardian(): void
    {
        $this->expectException(LogicException::class);

        HouseholdUser::guardianAttributes(HouseholdRole::Teen, null);
    }
}
