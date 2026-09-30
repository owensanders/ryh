<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Household;
use App\UseCases\House\CancelHouseholdSubscriptionUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CancelHouseholdSubscriptionUseCaseTest extends TestCase
{
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

        // No Stripe key in tests, so any cancel call would throw.
        app(CancelHouseholdSubscriptionUseCase::class)->execute($household);

        $this->assertSame(3, $household->subscriptions()->count());
    }
}
