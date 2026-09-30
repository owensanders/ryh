<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Household;
use Database\Seeders\HouseHubSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HouseholdPlanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function aHouseholdWithinItsTrialWindowIsOnTrial(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->addDays(10)]);

        $this->assertTrue($household->isOnTrial());
        $this->assertFalse($household->needsToSubscribe());
    }

    #[Test]
    public function aHouseholdPastItsTrialWithNoSubscriptionNeedsToSubscribe(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);

        $this->assertFalse($household->isOnTrial());
        $this->assertTrue($household->needsToSubscribe());
    }

    #[Test]
    public function aSubscribedHouseholdNeverNeedsToSubscribeEvenPastItsTrial(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => now()->subDay()]);
        Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_home_monthly_test',
        ]);

        $this->assertFalse($household->needsToSubscribe());
    }

    #[Test]
    public function aFailedRenewalIsAPaymentIssueNotASubscription(): void
    {
        $household = Household::factory()->create(['trial_ends_at' => null]);
        Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_test123',
            'stripe_status' => 'past_due',
            'stripe_price' => 'price_home_monthly_test',
        ]);

        $this->assertTrue($household->hasPaymentIssue());
        $this->assertTrue($household->needsToSubscribe());
    }

    #[Test]
    public function theDemoSeederRefusesToRunInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        (new HouseHubSeeder)->run();

        $this->assertDatabaseCount('households', 0);
    }
}
