<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Billing\HandleStripeEventOnceAction;
use App\Models\Household;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\FakesStripeTrait;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use FakesStripeTrait;
    use RefreshDatabase;

    private const string SECRET = 'whsec_test';

    #[Test]
    public function webhooksAreRefusedWhenNoSigningSecretIsConfigured(): void
    {
        config(['cashier.webhook.secret' => null]);
        $household = Household::factory()->create(['stripe_id' => 'cus_test123']);

        $this->postJson('/stripe/webhook', $this->forgedSubscriptionCreated('cus_test123'))
            ->assertStatus(503);

        $this->assertFalse($household->fresh()->subscribed('default'));
    }

    #[Test]
    public function unsignedWebhooksAreRejectedWhenASecretIsConfigured(): void
    {
        config(['cashier.webhook.secret' => self::SECRET]);
        $household = Household::factory()->create(['stripe_id' => 'cus_test123']);

        $this->postJson('/stripe/webhook', $this->forgedSubscriptionCreated('cus_test123'))
            ->assertForbidden();

        $this->assertFalse($household->fresh()->subscribed('default'));
    }

    #[Test]
    public function aSignedSubscriptionCreatedEventSubscribesTheHousehold(): void
    {
        $household = Household::factory()->create(['stripe_id' => 'cus_test123', 'trial_ends_at' => now()->subDay()]);

        $this->postStripeWebhook('customer.subscription.created', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly'))
            ->assertOk();

        $household->refresh();
        $this->assertTrue($household->subscribed('default'));
        $this->assertSame('sub_live', $household->subscription('default')->stripe_id);
        $this->assertNull($household->trial_ends_at);
        $this->assertFalse($household->needsToSubscribe());
    }

    #[Test]
    public function aSignedEventForAnUnknownCustomerChangesNothing(): void
    {
        $this->postStripeWebhook('customer.subscription.created', $this->stripeSubscription('sub_live', 'cus_nobody', 'price_monthly'))
            ->assertOk();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    #[Test]
    public function cancellingInThePortalKeepsAccessUntilTheEndOfThePaidPeriod(): void
    {
        $periodEnd = now()->addDays(12)->startOfSecond();
        // Cashier asks Stripe when the current period ends.
        $this->fakeStripe()->respond('GET', '/v1/subscription_items/si_live', $this->stripeFixture('subscription_item', [
            'id' => 'si_live',
            'subscription' => 'sub_live',
            'current_period_end' => $periodEnd->timestamp,
            'price' => ['id' => 'price_monthly'],
        ]));
        $household = $this->subscribedHousehold();

        $this->postStripeWebhook('customer.subscription.updated', [
            ...$this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly'),
            'cancel_at_period_end' => true,
        ])->assertOk();

        $subscription = $household->fresh()->subscription('default');
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertTrue($subscription->ends_at->equalTo($periodEnd));
        $this->assertTrue($household->fresh()->subscribed('default'));
    }

    #[Test]
    public function aFailedRenewalFlagsAPaymentIssueAndClosesAccess(): void
    {
        $household = $this->subscribedHousehold();

        $this->postStripeWebhook('customer.subscription.updated', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'past_due'))
            ->assertOk();

        $household->refresh();
        $this->assertTrue($household->hasPaymentIssue());
        $this->assertTrue($household->needsToSubscribe());
    }

    #[Test]
    public function switchingToAnnualInThePortalIsReflected(): void
    {
        $household = $this->subscribedHousehold();

        $this->postStripeWebhook('customer.subscription.updated', $this->stripeSubscription('sub_live', 'cus_test123', 'price_annual'))
            ->assertOk();

        $this->assertTrue($household->fresh()->subscription('default')->hasPrice('price_annual'));
    }

    #[Test]
    public function aSubscriptionEndingSendsTheHouseholdBackToThePaywall(): void
    {
        $household = $this->subscribedHousehold();

        $this->postStripeWebhook('customer.subscription.deleted', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'canceled'))
            ->assertOk();

        $household->refresh();
        $this->assertFalse($household->subscribed('default'));
        $this->assertTrue($household->needsToSubscribe());
        $this->assertFalse($household->hasPaymentIssue());
    }

    #[Test]
    public function anEventWhoseHandlingFailsIsLeftForStripesRetry(): void
    {
        $handleOnce = app(HandleStripeEventOnceAction::class);

        try {
            $handleOnce->handle('evt_flaky', 'invoice.paid', fn () => throw new RuntimeException('Database blip.'));
            $this->fail('The failure should have reached Stripe as an error.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseMissing('stripe_webhook_events', ['id' => 'evt_flaky']);

        $handled = 0;
        $this->assertTrue($handleOnce->handle('evt_flaky', 'invoice.paid', function () use (&$handled): void {
            $handled++;
        }));
        $this->assertFalse($handleOnce->handle('evt_flaky', 'invoice.paid', function () use (&$handled): void {
            $handled++;
        }));

        $this->assertSame(1, $handled);
        $this->assertDatabaseHas('stripe_webhook_events', ['id' => 'evt_flaky', 'type' => 'invoice.paid']);
    }

    private function subscribedHousehold(): Household
    {
        $household = Household::factory()->create(['stripe_id' => 'cus_test123', 'trial_ends_at' => null]);
        $subscription = Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_live',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
        ]);
        $subscription->items()->create([
            'stripe_id' => 'si_live',
            'stripe_product' => 'prod_home',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        return $household;
    }

    /** @return array<string, mixed> */
    private function forgedSubscriptionCreated(string $customer): array
    {
        return [
            'type' => 'customer.subscription.created',
            'data' => ['object' => [
                'id' => 'sub_forged',
                'customer' => $customer,
                'status' => 'active',
                'metadata' => ['type' => 'default'],
                'items' => ['data' => [[
                    'id' => 'si_forged',
                    'quantity' => 1,
                    'price' => ['id' => 'price_forged', 'product' => 'prod_forged'],
                ]]],
            ]],
        ];
    }
}
