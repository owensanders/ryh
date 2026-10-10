<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Mail\HouseholdInviteMail;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** A household holds up to `plans.max_members` people, pending invites and join requests included. */
class HouseholdMemberCapTest extends TestCase
{
    use RefreshDatabase;

    private const string FULL_INVITE_ERROR = 'Your household is full — it can have up to 8 members, including pending invites.';

    #[Test]
    public function theCapIsEightMembers(): void
    {
        $this->assertSame(8, config('plans.max_members'));
    }

    #[Test]
    public function aHouseholdIsFullOnceItReachesTheCap(): void
    {
        $owner = $this->ownerWithMembers(6);

        $this->assertFalse($this->isFull($owner));

        User::factory()->create(['household_id' => $owner->household_id]);

        $this->assertTrue($this->isFull($owner));
    }

    #[Test]
    public function pendingInvitesAndJoinRequestsHoldAPlace(): void
    {
        $owner = $this->ownerWithMembers(5);
        User::factory()->create(['household_id' => $owner->household_id, 'pending' => true, 'pending_reason' => PendingReasonEnum::Invited]);
        User::factory()->create(['household_id' => $owner->household_id, 'pending' => true, 'pending_reason' => PendingReasonEnum::Requested]);

        $this->assertTrue($this->isFull($owner));
    }

    #[Test]
    public function theOwnerCanInviteTheEighthMember(): void
    {
        Mail::fake();

        $owner = $this->ownerWithMembers(6);
        $existing = User::factory()->create(['email' => 'margaret@parkerhouse.co.uk']);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'margaret@parkerhouse.co.uk', 'role' => 'adult'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('toast', 'Invite sent to margaret@parkerhouse.co.uk');

        $this->assertDatabaseHas('household_user', ['household_id' => $owner->household_id, 'user_id' => $existing->id, 'pending' => true]);
        $this->assertTrue($this->isFull($owner));
    }

    #[Test]
    public function aFullHouseholdCantInviteSomeoneNew(): void
    {
        Mail::fake();

        $owner = $this->ownerWithMembers(7);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'margaret@parkerhouse.co.uk', 'role' => 'adult'])
            ->assertSessionHasErrors(['email' => self::FULL_INVITE_ERROR]);

        Mail::assertNothingQueued();
    }

    #[Test]
    public function aFullHouseholdCantInviteAnExistingUser(): void
    {
        Mail::fake();

        $owner = $this->ownerWithMembers(7);
        $existing = User::factory()->create(['email' => 'margaret@parkerhouse.co.uk']);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'margaret@parkerhouse.co.uk', 'role' => 'adult'])
            ->assertSessionHasErrors(['email' => self::FULL_INVITE_ERROR]);

        $this->assertDatabaseMissing('household_user', ['household_id' => $owner->household_id, 'user_id' => $existing->id]);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function inviteesAlreadyInTheHouseholdGetThatErrorRatherThanFull(): void
    {
        $owner = $this->ownerWithMembers(6);
        $member = User::factory()->create(['household_id' => $owner->household_id]);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => $member->email, 'role' => 'adult'])
            ->assertSessionHasErrors(['email' => 'This person is already part of the household.']);
    }

    #[Test]
    public function removingSomeoneFreesAPlace(): void
    {
        Mail::fake();

        $owner = $this->ownerWithMembers(7);
        $leaving = $owner->household->members()->where('users.id', '!=', $owner->id)->firstOrFail();

        $this->actingAs($owner)->delete(route('house.members.destroy', ['member' => $leaving->id]));

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'margaret@parkerhouse.co.uk', 'role' => 'adult'])
            ->assertSessionHasNoErrors();

        Mail::assertQueued(HouseholdInviteMail::class);
    }

    #[Test]
    public function someoneCanAskToJoinWithACodeWhileThereIsRoom(): void
    {
        $owner = $this->ownerWithMembers(6, ['join_code' => 'PARKER-4Q72KD']);
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)
            ->post('/household/join', ['code' => 'PARKER-4Q72KD'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('household.done', absolute: false));

        $this->assertDatabaseHas('household_user', ['household_id' => $owner->household_id, 'user_id' => $user->id, 'pending' => true]);
    }

    #[Test]
    public function aFullHouseholdTurnsAwayJoinRequests(): void
    {
        $owner = $this->ownerWithMembers(7, ['join_code' => 'PARKER-4Q72KD']);
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)
            ->post('/household/join', ['code' => 'PARKER-4Q72KD'])
            ->assertSessionHasErrors(['code' => 'That household is full, so it can\'t take any more members.']);

        $this->assertDatabaseMissing('household_user', ['household_id' => $owner->household_id, 'user_id' => $user->id]);
        $this->assertNull($user->refresh()->current_household_id);
    }

    #[Test]
    public function signingUpFromAnInviteToAHouseholdThatHasSinceFilledUpSendsThemToSetup(): void
    {
        $owner = $this->ownerWithMembers(7, ['name' => 'The Parkers']);

        $this->get($this->registerInviteUrl($owner->household, 'margaret@parkerhouse.co.uk'))->assertOk();

        $this->post('/register', [
            'name' => 'Margaret Parker',
            'email' => 'margaret@parkerhouse.co.uk',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ])
            ->assertRedirect(route('household.setup', absolute: false))
            ->assertSessionHas('fullHousehold', 'The Parkers');

        // Their account still exists — they just aren't in the full household.
        $this->assertAuthenticated();
        $user = User::where('email', 'margaret@parkerhouse.co.uk')->firstOrFail();
        $this->assertDatabaseMissing('household_user', ['household_id' => $owner->household_id, 'user_id' => $user->id]);
        $this->assertNull($user->current_household_id);
    }

    #[Test]
    public function theSetupPageExplainsWhenTheirInvitedHouseholdWasFull(): void
    {
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)
            ->withSession(['fullHousehold' => 'The Parkers'])
            ->get('/household/setup')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/HouseholdSetup')
                ->where('fullHousehold', 'The Parkers')
                ->where('maxMembers', 8)
            );
    }

    #[Test]
    public function theSetupPageHasNoFullNoticeNormally(): void
    {
        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)
            ->get('/household/setup')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fullHousehold', null));
    }

    #[Test]
    public function signingUpFromAnInviteStillWorksWhileThereIsRoom(): void
    {
        $owner = $this->ownerWithMembers(6);

        $this->get($this->registerInviteUrl($owner->household, 'margaret@parkerhouse.co.uk'));

        $this->post('/register', [
            'name' => 'Margaret Parker',
            'email' => 'margaret@parkerhouse.co.uk',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertTrue($this->isFull($owner));
    }

    #[Test]
    public function aNewHouseholdCanInviteSevenPeopleAtSetup(): void
    {
        Mail::fake();

        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)
            ->post('/household/setup', ['name' => 'The Parkers', 'invites' => $this->setupInvites(7)])
            ->assertSessionHasNoErrors();

        Mail::assertQueuedCount(7);
    }

    #[Test]
    public function aNewHouseholdCantInviteMoreThanSevenPeopleAtSetup(): void
    {
        Mail::fake();

        $user = User::factory()->create(['household_id' => null]);

        $this->actingAs($user)
            ->post('/household/setup', ['name' => 'The Parkers', 'invites' => $this->setupInvites(8)])
            ->assertSessionHasErrors(['invites' => 'You can invite up to 7 people — a household holds 8, including you.']);

        $this->assertDatabaseMissing('households', ['name' => 'The Parkers']);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function theHousePageShowsHowManyPlacesAreTaken(): void
    {
        $owner = $this->ownerWithMembers(4);
        User::factory()->create(['household_id' => $owner->household_id, 'pending' => true, 'pending_reason' => PendingReasonEnum::Invited]);

        $this->actingAs($owner)
            ->get('/house')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('house.memberCount', 6)
                ->where('house.maxMembers', 8)
            );
    }

    #[Test]
    public function theLandingPageAdvertisesTheCap(): void
    {
        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('plan.maxMembers', 8)
                ->where('plan.features', fn (Collection $features) => $features->contains('Up to 8 household members')
                    && ! $features->contains('Unlimited household members'))
            );
    }

    #[Test]
    public function theCapFollowsConfig(): void
    {
        config(['plans.max_members' => 3]);
        Mail::fake();

        $owner = $this->ownerWithMembers(2);

        $this->actingAs($owner)
            ->post('/house/invite', ['email' => 'margaret@parkerhouse.co.uk', 'role' => 'adult'])
            ->assertSessionHasErrors(['email' => 'Your household is full — it can have up to 3 members, including pending invites.']);
    }

    /**
     * An owner plus `$others` active members.
     *
     * @param  array<string, mixed>  $householdAttributes
     */
    private function ownerWithMembers(int $others, array $householdAttributes = []): User
    {
        $household = Household::factory()->create($householdAttributes);
        $owner = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);
        // Not `count()` — the factory only turns `household_id` into a membership on single creates.
        for ($i = 0; $i < $others; $i++) {
            User::factory()->create(['household_id' => $household->id]);
        }

        return $owner;
    }

    private function registerInviteUrl(Household $household, string $email): string
    {
        return URL::temporarySignedRoute('register', now()->addDays(7), [
            'invite_household' => $household->id,
            'invite_role' => 'adult',
            'email' => $email,
        ]);
    }

    /** @return list<array{email: string, role: string}> */
    private function setupInvites(int $count): array
    {
        return array_map(fn (int $i) => ['email' => "person{$i}@parkerhouse.co.uk", 'role' => 'adult'], range(1, $count));
    }

    private function isFull(User $owner): bool
    {
        return app(MembershipRepositoryInterface::class)->isFull($owner->household()->firstOrFail());
    }
}
