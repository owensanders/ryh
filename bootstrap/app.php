<?php

use App\Http\Middleware\EnsureHasHouseholdMiddleware;
use App\Http\Middleware\EnsureHouseholdRoleMiddleware;
use App\Http\Middleware\EnsureTrialActiveMiddleware;
use App\Http\Middleware\HandleInertiaRequestsMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::group([], base_path('routes/stripe.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequestsMiddleware::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => EnsureHouseholdRoleMiddleware::class,
            'household' => EnsureHasHouseholdMiddleware::class,
            'trial.active' => EnsureTrialActiveMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
