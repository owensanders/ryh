<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakesStripeTrait;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use FakesStripeTrait;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'plans.intervals.monthly.stripe_price' => 'price_monthly',
            'plans.intervals.annual.stripe_price' => 'price_annual',
        ]);
    }

    #[Test]
    public function subscribingFromThePaywallCreatesACustomerAndSendsTheOwnerToCheckout(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('POST', '/v1/customers', ['id' => 'cus_new', 'object' => 'customer'])
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession());
        $owner = $this->owner(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->post('/billing/subscribe', ['interval' => 'monthly'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test');

        $this->assertSame('cus_new', $owner->household->fresh()->stripe_id);
        $params = $stripe->assertRequested('POST', '/v1/checkout/sessions');
        $this->assertSame('cus_new', $params['customer']);
        $this->assertSame('subscription', $params['mode']);
        $this->assertSame('price_monthly', $params['line_items'][0]['price']);
        $this->assertSame(route('billing.checkout.complete').'?session_id={CHECKOUT_SESSION_ID}', $params['success_url']);
        $this->assertSame(route('billing.required').'?plan_checkout=cancelled', $params['cancel_url']);
    }

    #[Test]
    public function subscribingFromTheHouseTabUsesTheChosenIntervalAndReturnsThere(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('GET', '/v1/customers/cus_existing', ['id' => 'cus_existing', 'object' => 'customer'])
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession());
        $owner = $this->owner(['stripe_id' => 'cus_existing']);

        $this->actingAs($owner)->post('/house/subscribe', ['interval' => 'annual'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test');

        $stripe->assertNotRequested('POST', '/v1/customers');
        $params = $stripe->assertRequested('POST', '/v1/checkout/sessions');
        $this->assertSame('price_annual', $params['line_items'][0]['price']);
        $this->assertSame(route('billing.checkout.complete', ['from' => 'house']).'&session_id={CHECKOUT_SESSION_ID}', $params['success_url']);
    }

    #[Test]
    public function checkoutIsInertiaFriendly(): void
    {
        $this->fakeStripe()
            ->respond('POST', '/v1/customers', ['id' => 'cus_new', 'object' => 'customer'])
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession());
        $owner = $this->owner(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)
            ->post('/billing/subscribe', ['interval' => 'monthly'], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test');
    }

    #[Test]
    public function anAlreadySubscribedHouseholdIsNeverSentToCheckout(): void
    {
        $stripe = $this->fakeStripe();
        $owner = $this->owner(['stripe_id' => 'cus_existing']);
        $this->subscribe($owner->household);

        $this->actingAs($owner)->post('/house/subscribe', ['interval' => 'monthly'])
            ->assertSessionHas('toast', 'You are already subscribed');

        $stripe->assertNothingRequested();
    }

    #[Test]
    public function aPastDueHouseholdIsSentToThePortalToUpdateItsCardInsteadOfCheckout(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('POST', '/v1/billing_portal/sessions', $this->portalSession());
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => null]);
        $this->subscribe($owner->household, status: 'past_due');

        $this->actingAs($owner)->post('/billing/subscribe', ['interval' => 'monthly'])
            ->assertRedirect('https://billing.stripe.com/p/session/test');

        $stripe->assertNotRequested('POST', '/v1/checkout/sessions');
        $params = $stripe->assertRequested('POST', '/v1/billing_portal/sessions');
        $this->assertSame('cus_existing', $params['customer']);
        $this->assertSame(route('billing.required'), $params['return_url']);
    }

    #[Test]
    public function stripeFailingToStartCheckoutShowsAnErrorInsteadOfCrashing(): void
    {
        $this->fakeStripe()
            ->respond('POST', '/v1/customers', ['id' => 'cus_new', 'object' => 'customer'])
            ->respond('POST', '/v1/checkout/sessions', ['error' => ['type' => 'invalid_request_error', 'message' => 'No such price']], 400);
        $owner = $this->owner(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->post('/billing/subscribe', ['interval' => 'monthly'])
            ->assertSessionHasErrors(['plan' => "We couldn't reach our payment provider. Please try again in a moment."]);
    }

    #[Test]
    public function stripeFailingToOpenThePortalShowsAnErrorInsteadOfCrashing(): void
    {
        $this->fakeStripe()
            ->respond('POST', '/v1/billing_portal/sessions', ['error' => ['type' => 'api_error', 'message' => 'Something went wrong']], 500);
        $owner = $this->owner(['stripe_id' => 'cus_existing']);

        $this->actingAs($owner)->post('/billing/portal')
            ->assertSessionHasErrors(['plan' => "We couldn't reach our payment provider. Please try again in a moment."]);
    }

    #[Test]
    public function returningFromCheckoutRecordsTheSubscriptionStraightAway(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession(
                subscription: $this->stripeSubscription('sub_new', 'cus_existing', 'price_annual'),
            ));
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->get('/billing/checkout/complete?session_id=cs_test')
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('toast');

        $household = $owner->household->fresh();
        $this->assertTrue($household->subscribed('default'));
        $this->assertTrue($household->subscription('default')->hasPrice('price_annual'));
        $this->assertSame('sub_new', $household->subscription('default')->stripe_id);
        $this->assertNull($household->trial_ends_at);
        $this->assertSame(['subscription'], $stripe->assertRequested('GET', '/v1/checkout/sessions/cs_test')['expand']);

        // A fresh user, since $owner's cached household predates the subscription.
        $this->actingAs($owner->fresh())->get('/dashboard')->assertOk();
    }

    #[Test]
    public function aCheckoutSessionForAnotherHouseholdIsIgnored(): void
    {
        $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession(
                customer: 'cus_someone_else',
                subscription: $this->stripeSubscription('sub_theirs', 'cus_someone_else', 'price_monthly'),
            ));
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->get('/billing/checkout/complete?session_id=cs_test');

        $this->assertFalse($owner->household->fresh()->subscribed('default'));
        $this->assertDatabaseMissing('subscriptions', ['stripe_id' => 'sub_theirs']);
    }

    #[Test]
    public function anUnfinishedCheckoutSessionIsIgnored(): void
    {
        $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession(status: 'open'));
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->get('/billing/checkout/complete?session_id=cs_test');

        $this->assertFalse($owner->household->fresh()->subscribed('default'));
    }

    #[Test]
    public function aStripeErrorOnReturnFromCheckoutFallsBackToTheWebhook(): void
    {
        $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_bogus', ['error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such checkout.session']], 404);
        $owner = $this->owner(['stripe_id' => 'cus_existing']);

        $this->actingAs($owner)->get('/billing/checkout/complete?session_id=cs_bogus&from=house')
            ->assertRedirect(route('house.index', absolute: false));

        $this->assertFalse($owner->household->fresh()->subscribed('default'));
    }

    #[Test]
    public function returningFromCheckoutAfterTheWebhookLandedDoesNothingMore(): void
    {
        $stripe = $this->fakeStripe();
        $owner = $this->owner(['stripe_id' => 'cus_existing']);
        $this->subscribe($owner->household);

        $this->actingAs($owner)->get('/billing/checkout/complete?session_id=cs_test')
            ->assertRedirect(route('dashboard', absolute: false));

        $stripe->assertNothingRequested();
        $this->assertSame(1, $owner->household->subscriptions()->count());
    }

    #[Test]
    public function manageBillingOpensTheStripePortalAndReturnsToTheHouseTab(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('POST', '/v1/billing_portal/sessions', $this->portalSession());
        $owner = $this->owner(['stripe_id' => 'cus_existing']);
        $this->subscribe($owner->household);

        $this->actingAs($owner)->post('/billing/portal', [], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://billing.stripe.com/p/session/test');

        $params = $stripe->assertRequested('POST', '/v1/billing_portal/sessions');
        $this->assertSame('cus_existing', $params['customer']);
        $this->assertSame(route('house.index'), $params['return_url']);
    }

    /** @param  array<string, mixed>  $attributes */
    private function owner(array $attributes = []): User
    {
        $household = Household::factory()->create($attributes);

        return User::factory()->create(['household_id' => $household->id, 'role' => HouseholdRoleEnum::Owner]);
    }

    private function subscribe(Household $household, string $status = 'active'): void
    {
        Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_existing',
            'stripe_status' => $status,
            'stripe_price' => 'price_monthly',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $subscription
     * @return array<string, mixed>
     */
    private function checkoutSession(string $customer = 'cus_existing', string $status = 'complete', ?array $subscription = null): array
    {
        return [
            'id' => 'cs_test',
            'object' => 'checkout.session',
            'customer' => $customer,
            'mode' => 'subscription',
            'status' => $status,
            'subscription' => $subscription,
            'url' => 'https://checkout.stripe.com/c/pay/cs_test',
        ];
    }

    /** @return array<string, mixed> */
    private function portalSession(): array
    {
        return [
            'id' => 'bps_test',
            'object' => 'billing_portal.session',
            'url' => 'https://billing.stripe.com/p/session/test',
        ];
    }
}
