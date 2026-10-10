<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteAuthorizationTest extends TestCase
{
    /** The bell reads and clears each person's own notifications, so there's nothing in the household to authorize. */
    private const array PER_PERSON = ['notifications.index', 'notifications.read', 'notifications.readAll', 'notifications.destroyAll'];

    #[Test]
    public function everyHouseholdRouteNamesItsPolicyAbility(): void
    {
        $missing = collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('household', $route->gatherMiddleware(), true))
            ->reject(fn (Route $route) => in_array($route->getName(), self::PER_PERSON, true))
            ->reject(fn (Route $route) => collect($route->gatherMiddleware())->contains(fn ($middleware) => is_string($middleware) && Str::startsWith($middleware, 'can:')))
            ->map(fn (Route $route) => implode('|', $route->methods()).' '.$route->uri())
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Add ->can(...) to these routes, backed by a policy in app/Policies.');
    }
}
