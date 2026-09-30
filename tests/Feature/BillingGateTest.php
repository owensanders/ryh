<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingGateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function aHouseholdPastItsTrialWithNoSubscriptionIsRedirectedToBilling(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['household_id' => $household->id]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('billing.required', absolute: false));
    }

    #[Test]
    public function aHouseholdStillWithinItsTrialReachesTheDashboard(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['household_id' => $household->id]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    #[Test]
    public function aSubscribedHouseholdPastItsTrialStillReachesTheDashboard(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_home_monthly_test',
        ]);
        $user = User::factory()->create(['household_id' => $household->id]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    #[Test]
    public function thePaywallItselfIsReachableEvenPastTheTrial(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)->get('/billing/required')->assertOk();
    }

    #[Test]
    public function onlyTheOwnerCanStartCheckoutFromThePaywall(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($user)->post('/billing/subscribe')->assertForbidden();
    }

    #[Test]
    public function subscribingRequiresAValidInterval(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)->post('/billing/subscribe', ['interval' => 'weekly'])
            ->assertSessionHasErrors('interval');
    }

    #[Test]
    public function subscribingFailsCleanlyWhenBillingIsNotConfigured(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)->post('/billing/subscribe', ['interval' => 'annual'])
            ->assertSessionHasErrors('plan');
    }

    #[Test]
    public function onlyTheOwnerCanOpenTheBillingPortal(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($user)->post('/billing/portal')->assertForbidden();
    }

    #[Test]
    public function theBillingPortalNeedsAStripeCustomer(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)->post('/billing/portal')->assertSessionHasErrors('plan');
    }

    #[Test]
    public function onlyTheOwnerCanCompleteCheckout(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Adult]);

        $this->actingAs($user)->get('/billing/checkout/complete')->assertForbidden();
    }

    #[Test]
    public function completingCheckoutReturnsTheOwnerToWhereTheyStarted(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)->get('/billing/checkout/complete')
            ->assertRedirect(route('dashboard', absolute: false));
        $this->actingAs($user)->get('/billing/checkout/complete?from=house')
            ->assertRedirect(route('house.index', absolute: false));
    }

    #[Test]
    public function aPastDueHouseholdIsAskedToUpdateItsCardNotSubscribeAgain(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => null]);
        Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'past_due',
            'stripe_price' => 'price_home_monthly_test',
        ]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('billing.required', absolute: false));
        $this->actingAs($user)->get('/billing/required')
            ->assertInertia(fn ($page) => $page->where('paymentIssue', true));
    }

    #[Test]
    public function thePaywallSendsSubscribedHouseholdsBackToTheApp(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRole::Owner]);

        $this->actingAs($user)->get('/billing/required')->assertRedirect(route('dashboard', absolute: false));
    }
}
