<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Household;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\FakesStripe;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use FakesStripe;
    use RefreshDatabase;

    private const SECRET = 'whsec_test';

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

        $this->postSigned('customer.subscription.created', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly'))
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
        $this->postSigned('customer.subscription.created', $this->stripeSubscription('sub_live', 'cus_nobody', 'price_monthly'))
            ->assertOk();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    #[Test]
    public function cancellingInThePortalKeepsAccessUntilTheEndOfThePaidPeriod(): void
    {
        $periodEnd = now()->addDays(12)->startOfSecond();
        // Cashier asks Stripe when the current period ends.
        $this->fakeStripe()->respond('GET', '/v1/subscription_items/si_live', [
            'id' => 'si_live', 'object' => 'subscription_item', 'quantity' => 1,
            'current_period_end' => $periodEnd->timestamp,
            'price' => ['id' => 'price_monthly', 'object' => 'price', 'product' => 'prod_home'],
        ]);
        $household = $this->subscribedHousehold();

        $this->postSigned('customer.subscription.updated', [
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

        $this->postSigned('customer.subscription.updated', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'past_due'))
            ->assertOk();

        $household->refresh();
        $this->assertTrue($household->hasPaymentIssue());
        $this->assertTrue($household->needsToSubscribe());
    }

    #[Test]
    public function switchingToAnnualInThePortalIsReflected(): void
    {
        $household = $this->subscribedHousehold();

        $this->postSigned('customer.subscription.updated', $this->stripeSubscription('sub_live', 'cus_test123', 'price_annual'))
            ->assertOk();

        $this->assertTrue($household->fresh()->subscription('default')->hasPrice('price_annual'));
    }

    #[Test]
    public function aSubscriptionEndingSendsTheHouseholdBackToThePaywall(): void
    {
        $household = $this->subscribedHousehold();

        $this->postSigned('customer.subscription.deleted', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'canceled'))
            ->assertOk();

        $household->refresh();
        $this->assertFalse($household->subscribed('default'));
        $this->assertTrue($household->needsToSubscribe());
        $this->assertFalse($household->hasPaymentIssue());
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

    /**
     * @param  array<string, mixed>  $object
     * @return TestResponse<Response>
     */
    private function postSigned(string $type, array $object): TestResponse
    {
        config(['cashier.webhook.secret' => self::SECRET]);

        $payload = json_encode(['id' => 'evt_test', 'type' => $type, 'data' => ['object' => $object]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);

        return $this->call('POST', '/stripe/webhook', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], content: $payload);
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
