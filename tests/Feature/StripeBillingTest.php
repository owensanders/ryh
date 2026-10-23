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
            ->respond('POST', '/v1/customers', $this->stripeFixture('customer', ['id' => 'cus_new']))
            // Cashier fetches the customer again on its way into Checkout.
            ->respond('GET', '/v1/customers/cus_new', $this->stripeFixture('customer', ['id' => 'cus_new']))
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession());
        $owner = $this->owner(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'monthly'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test');

        $this->assertSame('cus_new', $owner->lastHousehold->fresh()->stripe_id);
        $params = $stripe->assertRequested('POST', '/v1/checkout/sessions');
        $this->assertSame('cus_new', $params['customer']);
        $this->assertSame('subscription', $params['mode']);
        $this->assertSame('price_monthly', $params['line_items'][0]['price']);
        $this->assertSame(route('billing.checkout.complete').'?session_id={CHECKOUT_SESSION_ID}', $params['success_url']);
        $this->assertSame(route('billing.required').'?plan_checkout=cancelled', $params['cancel_url']);
    }

    #[Test]
    public function creatingTheStripeCustomerCarriesAnIdempotencyKey(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('POST', '/v1/customers', $this->stripeFixture('customer', ['id' => 'cus_new']))
            ->respond('GET', '/v1/customers/cus_new', $this->stripeFixture('customer', ['id' => 'cus_new']))
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession());
        $owner = $this->owner(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'monthly']);

        $key = $stripe->headerOf('POST', '/v1/customers', 'Idempotency-Key');
        $this->assertNotNull($key);
        $this->assertStringStartsWith("household-{$owner->household_id}-customer-", $key);
    }

    #[Test]
    public function aSecondSubscribeReusesTheFirstsCheckoutSessionButADifferentPlanGetsItsOwn(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('GET', '/v1/customers/cus_existing', $this->stripeFixture('customer', ['id' => 'cus_existing']))
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession())
            // Reused only while Stripe still has it open.
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession());
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        // A double-click, or Back from Checkout and Subscribe again.
        foreach ([1, 2] as $click) {
            $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'monthly'])
                ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test');
        }

        $sessions = fn (): int => collect($stripe->requests)->where('method', 'POST')->where('path', '/v1/checkout/sessions')->count();
        $this->assertSame(1, $sessions());

        $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'annual']);
        $this->assertSame(2, $sessions());
    }

    #[Test]
    public function subscribingFromTheHouseTabUsesTheChosenIntervalAndReturnsThere(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('GET', '/v1/customers/cus_existing', $this->stripeFixture('customer', ['id' => 'cus_existing']))
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession());
        $owner = $this->owner(['stripe_id' => 'cus_existing']);

        $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'annual', 'from' => 'house'])
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
            ->respond('POST', '/v1/customers', $this->stripeFixture('customer', ['id' => 'cus_new']))
            // Cashier fetches the customer again on its way into Checkout.
            ->respond('GET', '/v1/customers/cus_new', $this->stripeFixture('customer', ['id' => 'cus_new']))
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession());
        $owner = $this->owner(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)
            ->post($this->hh('/billing/subscribe'), ['interval' => 'monthly'], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test');
    }

    #[Test]
    public function anAlreadySubscribedHouseholdIsNeverSentToCheckout(): void
    {
        $stripe = $this->fakeStripe();
        $owner = $this->owner(['stripe_id' => 'cus_existing']);
        $this->subscribe($owner->lastHousehold);

        $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'monthly', 'from' => 'house'])
            ->assertSessionHas('toast', 'You are already subscribed');

        $stripe->assertNothingRequested();
    }

    #[Test]
    public function aPastDueHouseholdIsSentToThePortalToUpdateItsCardInsteadOfCheckout(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('POST', '/v1/billing_portal/sessions', $this->portalSession());
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => null]);
        $this->subscribe($owner->lastHousehold, status: 'past_due');

        $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'monthly'])
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
            ->respond('POST', '/v1/customers', $this->stripeFixture('customer', ['id' => 'cus_new']))
            // Cashier fetches the customer again on its way into Checkout.
            ->respond('GET', '/v1/customers/cus_new', $this->stripeFixture('customer', ['id' => 'cus_new']))
            ->respond('POST', '/v1/checkout/sessions', ['error' => ['type' => 'invalid_request_error', 'message' => 'No such price']], 400);
        $owner = $this->owner(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'monthly'])
            ->assertSessionHasErrors(['plan' => "We couldn't reach our payment provider. Please try again in a moment."]);
    }

    #[Test]
    public function stripeFailingToOpenThePortalShowsAnErrorInsteadOfCrashing(): void
    {
        $this->fakeStripe()
            ->respond('POST', '/v1/billing_portal/sessions', ['error' => ['type' => 'api_error', 'message' => 'Something went wrong']], 500);
        $owner = $this->owner(['stripe_id' => 'cus_existing']);

        $this->actingAs($owner)->post($this->hh('/billing/portal'))
            ->assertSessionHasErrors(['plan' => "We couldn't reach our payment provider. Please try again in a moment."]);
    }

    #[Test]
    public function aRememberedCheckoutSessionThatIsNoLongerOpenGetsANewOne(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('GET', '/v1/customers/cus_existing', $this->stripeFixture('customer', ['id' => 'cus_existing']))
            ->respond('POST', '/v1/checkout/sessions', $this->checkoutSession())
            // Paid in another tab, or expired, since it was remembered.
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession(status: 'expired'));
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        foreach ([1, 2] as $click) {
            $this->actingAs($owner)->post($this->hh('/billing/subscribe'), ['interval' => 'monthly']);
        }

        $this->assertSame(2, collect($stripe->requests)->where('method', 'POST')->where('path', '/v1/checkout/sessions')->count());
    }

    #[Test]
    public function returningFromAPaidCheckoutSaysItsBeingConfirmedUntilTheWebhookRecordsIt(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession(status: 'complete'));
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->get($this->hh('/billing/checkout/complete?session_id=cs_test'))
            ->assertRedirect($this->hh('/dashboard'))
            ->assertSessionHas('toast', "Thanks! We're confirming your payment with Stripe; your plan will update in a moment.");
        $stripe->assertRequested('GET', '/v1/checkout/sessions/cs_test');

        // Still unsubscribed, so the paywall waits for the webhook rather than asking again.
        $this->actingAs($owner)->get($this->hh('/billing/required'))
            ->assertInertia(fn ($page) => $page->component('BillingRequired')->where('confirmingPayment', true));

        $this->postStripeWebhook('customer.subscription.created', $this->stripeSubscription('sub_new', 'cus_existing', 'price_annual'))->assertOk();

        $household = $owner->lastHousehold->fresh();
        $this->assertTrue($household->subscribed('default'));
        $this->assertTrue($household->subscription('default')->hasPrice('price_annual'));
        $this->assertFalse($household->isConfirmingPayment());
        $this->actingAs($owner->fresh())->get($this->hh('/billing/required'))->assertRedirect($this->hh('/dashboard'));
    }

    #[Test]
    public function aCheckoutSessionForAnotherHouseholdIsIgnored(): void
    {
        $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession(customer: 'cus_someone_else', status: 'complete'));
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->get($this->hh('/billing/checkout/complete?session_id=cs_test'));

        $this->assertFalse($owner->lastHousehold->fresh()->isConfirmingPayment());
    }

    #[Test]
    public function anUnfinishedCheckoutSessionIsIgnored(): void
    {
        $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_test', $this->checkoutSession(status: 'open'));
        $owner = $this->owner(['stripe_id' => 'cus_existing', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->get($this->hh('/billing/checkout/complete?session_id=cs_test'));

        $this->actingAs($owner)->get($this->hh('/billing/required'))
            ->assertInertia(fn ($page) => $page->where('confirmingPayment', false));
    }

    #[Test]
    public function aStripeErrorOnReturnFromCheckoutFallsBackToTheWebhook(): void
    {
        $this->fakeStripe()
            ->respond('GET', '/v1/checkout/sessions/cs_bogus', ['error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such checkout.session']], 404);
        $owner = $this->owner(['stripe_id' => 'cus_existing']);

        $this->actingAs($owner)->get($this->hh('/billing/checkout/complete?session_id=cs_bogus&from=house'))
            ->assertRedirect(route('house.index', absolute: false));

        $this->assertFalse($owner->lastHousehold->fresh()->isConfirmingPayment());
    }

    #[Test]
    public function returningFromCheckoutAfterTheWebhookLandedSaysSo(): void
    {
        $stripe = $this->fakeStripe();
        $owner = $this->owner(['stripe_id' => 'cus_existing']);
        $this->subscribe($owner->lastHousehold);

        $this->actingAs($owner)->get($this->hh('/billing/checkout/complete?session_id=cs_test'))
            ->assertRedirect($this->hh('/dashboard'))
            ->assertSessionHas('toast', "You're subscribed. Thanks!");

        $stripe->assertNothingRequested();
    }

    #[Test]
    public function manageBillingOpensTheStripePortalAndReturnsToTheHouseTab(): void
    {
        $stripe = $this->fakeStripe()
            ->respond('POST', '/v1/billing_portal/sessions', $this->portalSession());
        $owner = $this->owner(['stripe_id' => 'cus_existing']);
        $this->subscribe($owner->lastHousehold);

        $this->actingAs($owner)->post($this->hh('/billing/portal'), [], ['X-Inertia' => 'true'])
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

    /** @return array<string, mixed> A Checkout session, as Stripe returns it while open or once paid. */
    private function checkoutSession(string $customer = 'cus_existing', string $status = 'open'): array
    {
        return $this->stripeFixture('checkout.session', [
            'id' => 'cs_test',
            'customer' => $customer,
            'status' => $status,
            'payment_status' => $status === 'complete' ? 'paid' : 'unpaid',
            'subscription' => $status === 'complete' ? 'sub_new' : null,
            'url' => 'https://checkout.stripe.com/c/pay/cs_test',
        ]);
    }

    /** @return array<string, mixed> */
    private function portalSession(): array
    {
        return $this->stripeFixture('billing_portal.session', ['url' => 'https://billing.stripe.com/p/session/test']);
    }
}
