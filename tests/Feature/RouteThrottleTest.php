<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteThrottleTest extends TestCase
{
    /**
     * Writes that are limited some other way: login counts failed attempts
     * itself (LoginRequest), Stripe's webhook is signed and retries in
     * bursts we must accept, and the local disk's upload route ('serve' in
     * config/filesystems.php) refuses anything without a signed URL, which
     * the app never issues.
     */
    private const array LIMITED_ELSEWHERE = ['POST login', 'POST stripe/webhook', 'PUT storage/{path}'];

    #[Test]
    public function everyWriteRouteIsRateLimited(): void
    {
        $missing = collect(Router::getRoutes()->getRoutes())
            ->reject(fn (Route $route) => in_array('GET', $route->methods(), true))
            ->map(fn (Route $route) => [implode('|', $route->methods()).' '.$route->uri(), $route])
            ->reject(fn (array $entry) => in_array($entry[0], self::LIMITED_ELSEWHERE, true))
            ->reject(fn (array $entry) => collect($entry[1]->gatherMiddleware())->contains(fn ($middleware) => is_string($middleware) && Str::startsWith($middleware, 'throttle:')))
            ->map(fn (array $entry) => $entry[0])
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Add throttle middleware to these routes: a named limiter from AppServiceProvider, or throttle:app on the group.');
    }
}
