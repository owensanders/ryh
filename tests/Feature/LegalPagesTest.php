<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function pages(): array
    {
        return [
            'privacy' => ['/privacy', 'legal/Privacy'],
            'terms' => ['/terms', 'legal/Terms'],
            'support' => ['/support', 'Support'],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function itRendersForGuests(string $url, string $component): void
    {
        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($component)
                ->where('legal.tradingName', 'HouseHub')
                ->where('legal.termsVersion', config('legal.terms_version')));
    }

    #[Test]
    #[DataProvider('pages')]
    public function itRendersForSignedInUsers(string $url, string $component): void
    {
        $this->actingAs(User::factory()->create())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
    }

    #[Test]
    public function unsetBusinessDetailsAreSentAsNullSoThePageFlagsThem(): void
    {
        config(['legal.entity_name' => null, 'legal.support_email' => null, 'legal.privacy_email' => null]);

        $this->get('/terms')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('legal.entityName', null)
                ->where('legal.supportEmail', null));
    }

    #[Test]
    public function itPassesConfiguredBusinessDetailsThrough(): void
    {
        config([
            'legal.entity_name' => 'HouseHub Ltd',
            'legal.entity_type' => 'limited_company',
            'legal.company_number' => '12345678',
            'legal.support_email' => 'help@househub.test',
            'legal.privacy_email' => 'privacy@househub.test',
        ]);

        $this->get('/privacy')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('legal.entityName', 'HouseHub Ltd')
                ->where('legal.entityType', 'limited_company')
                ->where('legal.companyNumber', '12345678')
                ->where('legal.supportEmail', 'help@househub.test')
                ->where('legal.privacyEmail', 'privacy@househub.test'));
    }

    #[Test]
    public function theTermsQuoteTheCurrentPlanPrices(): void
    {
        $this->get('/terms')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('plan.priceMonthly', config('plans.intervals.monthly.price'))
                ->where('plan.priceAnnual', config('plans.intervals.annual.price')));
    }
}
