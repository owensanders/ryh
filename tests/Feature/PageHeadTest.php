<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tighten\Ziggy\BladeRouteGenerator;

/**
 * What the HTML itself says, before any JavaScript runs: the title and
 * preview tags link previews and crawlers read (config/meta.php), and that
 * the browser is never told the internal routes (config/ziggy.php).
 */
class PageHeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ziggy prints its full script once per PHP process, then only merges: a test runs many requests in one.
        BladeRouteGenerator::$generated = false;
    }

    #[Test]
    public function theLandingPageCarriesItsPreviewInTheHtml(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title data-inertia>Your home, organised - RYH</title>', false)
            ->assertSee('<meta property="og:title" content="Your home, organised - RYH">', false)
            ->assertSee('<meta name="description" content="'.e(config('meta.description')).'">', false)
            ->assertSee('<meta property="og:image" content="'.url('/videos/ryh-promo-poster.jpg').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            // The head's own variables mustn't clobber Inertia's page data.
            ->assertSee('"component":"Welcome"', false);
    }

    #[Test]
    public function aPublicPageHasItsOwnTitleAndDescription(): void
    {
        $this->get('/privacy')
            ->assertSee('<title data-inertia>Privacy Policy - RYH</title>', false)
            ->assertSee('<meta name="description" content="'.e(config('meta.pages.privacy.description')).'">', false);
    }

    #[Test]
    public function theBrowserIsNeverToldTheInternalRoutes(): void
    {
        $signedOut = fn () => $this->get('/login');
        $signedIn = fn () => $this->actingAs(User::factory()->create())->get($this->hh('/dashboard'));

        foreach ([$signedOut, $signedIn] as $visit) {
            BladeRouteGenerator::$generated = false;
            $routes = $this->ziggyRoutes($visit());

            $this->assertContains('register', $routes);
            $this->assertSame([], array_values(array_filter($routes, fn (string $name) => preg_match('/^(telescope|cashier|storage)/', $name) === 1)));
        }
    }

    /**
     * @param  TestResponse<Response>  $response
     *
     * @return list<string> The route names Ziggy put in the page.
     */
    private function ziggyRoutes(TestResponse $response): array
    {
        $found = preg_match('/const Ziggy=(\{.*?\});/s', (string) $response->getContent(), $match);
        $this->assertSame(1, $found, 'The page has no Ziggy routes.');

        /** @var array{routes: array<string, mixed>} $ziggy */
        $ziggy = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);

        return array_keys($ziggy['routes']);
    }
}
