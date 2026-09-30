<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Household;
use App\UseCases\House\CancelHouseholdSubscriptionUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Exception\ApiErrorException;
use Tests\Support\FakesStripe;
use Tests\TestCase;

class CancelHouseholdSubscriptionUseCaseTest extends TestCase
{
    use FakesStripe;
    use RefreshDatabase;

    #[Test]
    public function subscriptionsThatWontChargeAgainAreLeftAlone(): void
    {
        $household = Household::factory()->create();
        foreach ([
            ['sub_grace', 'active', now()->addMonth()],
            ['sub_ended', 'canceled', now()->subMonth()],
            ['sub_expired', 'incomplete_expired', null],
        ] as [$id, $status, $endsAt]) {
            Subscription::create([
                'household_id' => $household->id,
                'type' => 'default',
                'stripe_id' => $id,
                'stripe_status' => $status,
                'stripe_price' => 'price_test',
                'ends_at' => $endsAt,
            ]);
        }

        $stripe = $this->fakeStripe();

        app(CancelHouseholdSubscriptionUseCase::class)->execute($household);

        $this->assertSame(3, $household->subscriptions()->count());
        $stripe->assertNothingRequested();
    }

    #[Test]
    public function anActiveSubscriptionIsCancelledImmediately(): void
    {
        $stripe = $this->fakeStripe()->respond('DELETE', '/v1/subscriptions/sub_live', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'canceled'));
        $household = $this->householdWith('active');

        app(CancelHouseholdSubscriptionUseCase::class)->execute($household);

        $stripe->assertRequested('DELETE', '/v1/subscriptions/sub_live');
        $subscription = Subscription::where('stripe_id', 'sub_live')->sole();
        $this->assertSame('canceled', $subscription->stripe_status);
        $this->assertTrue($subscription->ended());
    }

    #[Test]
    public function aPastDueSubscriptionIsCancelledTooSoStripeStopsRetrying(): void
    {
        $stripe = $this->fakeStripe()->respond('DELETE', '/v1/subscriptions/sub_live', $this->stripeSubscription('sub_live', 'cus_test123', 'price_monthly', 'canceled'));

        app(CancelHouseholdSubscriptionUseCase::class)->execute($this->householdWith('past_due'));

        $stripe->assertRequested('DELETE', '/v1/subscriptions/sub_live');
    }

    #[Test]
    public function aSubscriptionAlreadyGoneFromStripeIsSkipped(): void
    {
        $this->fakeStripe()->respond('DELETE', '/v1/subscriptions/sub_live', [
            'error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such subscription'],
        ], 404);

        app(CancelHouseholdSubscriptionUseCase::class)->execute($this->householdWith('active'));

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function anyOtherStripeErrorStopsTheDeletion(): void
    {
        $this->fakeStripe()->respond('DELETE', '/v1/subscriptions/sub_live', [
            'error' => ['type' => 'api_error', 'message' => 'Something went wrong'],
        ], 500);

        $this->expectException(ApiErrorException::class);

        app(CancelHouseholdSubscriptionUseCase::class)->execute($this->householdWith('active'));
    }

    private function householdWith(string $status): Household
    {
        $household = Household::factory()->create(['stripe_id' => 'cus_test123']);
        Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_live',
            'stripe_status' => $status,
            'stripe_price' => 'price_monthly',
        ]);

        return $household;
    }
}
