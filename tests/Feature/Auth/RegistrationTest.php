<?php

namespace Tests\Feature\Auth;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistrationScreenCanBeRendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    #[Test]
    public function itPrefillsTheEmailFromTheLandingPageCta(): void
    {
        $this->get('/register?email=you@household.co.uk')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('email', 'you@household.co.uk'));
    }

    #[Test]
    public function itIgnoresANonStringEmailQueryParameter(): void
    {
        $this->get('/register?email[]=you@household.co.uk')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('email', ''));
    }

    public function testNewUsersCanRegister(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('household.setup', absolute: false));
    }

    #[Test]
    public function itRecordsWhichTermsVersionTheUserAccepted(): void
    {
        // The column stores whole seconds.
        $this->freezeSecond();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(config('legal.terms_version'), $user->terms_version);
        $this->assertTrue($user->terms_accepted_at?->equalTo(now()));
    }

    #[Test]
    public function itRejectsRegistrationWithoutAcceptingTheTerms(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'a-very-strong-password',
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
    }

    #[Test]
    public function itTellsTheFormWhetherTheVisitorWasInvited(): void
    {
        $this->get('/register')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invited', false));

        $inviteUrl = URL::temporarySignedRoute('register', now()->addDays(7), [
            'invite_household' => Household::factory()->create()->id,
            'invite_role' => 'child',
        ]);

        $this->get($inviteUrl)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invited', true));
    }

    #[Test]
    public function registeringViaASignedInviteLinkJoinsTheHouseholdAndReachesTheDashboard(): void
    {
        $household = Household::factory()->create();

        $inviteUrl = URL::temporarySignedRoute('register', now()->addDays(7), [
            'invite_household' => $household->id,
            'invite_role' => 'adult',
            'email' => 'margaret@parkerhouse.co.uk',
        ]);

        $this->get($inviteUrl)->assertOk();

        $response = $this->post('/register', [
            'name' => 'Margaret Parker',
            'email' => 'margaret@parkerhouse.co.uk',
            'password' => 'a-very-strong-password',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'margaret@parkerhouse.co.uk')->firstOrFail();
        $this->assertSame($household->id, $user->current_household_id);
        $this->assertDatabaseHas('household_user', [
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => 'adult',
            'pending' => false,
        ]);
    }
}
