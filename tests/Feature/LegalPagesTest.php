<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
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
            'cookies' => ['/cookies', 'legal/Cookies'],
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
                ->where('legal.tradingName', 'RYH')
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
            'legal.entity_name' => 'RYH Ltd',
            'legal.entity_type' => 'limited_company',
            'legal.company_number' => '12345678',
            'legal.support_email' => 'help@ryh.test',
            'legal.privacy_email' => 'privacy@ryh.test',
        ]);

        $this->get('/privacy')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('legal.entityName', 'RYH Ltd')
                ->where('legal.entityType', 'limited_company')
                ->where('legal.companyNumber', '12345678')
                ->where('legal.supportEmail', 'help@ryh.test')
                ->where('legal.privacyEmail', 'privacy@ryh.test'));
    }

    #[Test]
    public function theCookiePolicyDescribesTheCookiesTheAppActuallySets(): void
    {
        config(['session.cookie' => 'ryh_session', 'session.lifetime' => 120, 'session.expire_on_close' => false]);

        $this->get('/cookies')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('cookies.sessionCookie', 'ryh_session')
                ->where('cookies.sessionLifetimeMinutes', 120)
                ->where('cookies.expireOnClose', false)
                ->where('cookies.rememberDays', 400)
                ->where('legal.cookiesVersion', config('legal.cookies_version')));
    }

    #[Test]
    public function theSessionCookieItDescribesIsTheOneSentToVisitors(): void
    {
        $this->get('/cookies')->assertCookie(config('session.cookie'))->assertCookie('XSRF-TOKEN');
    }

    #[Test]
    public function theRememberMeCookieLastsAsLongAsThePolicySays(): void
    {
        $this->assertSame(400 * 24 * 60, (new \ReflectionProperty(SessionGuard::class, 'rememberDuration'))->getDefaultValue());
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
