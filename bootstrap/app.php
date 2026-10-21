<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureTrialActiveMiddleware;
use App\Http\Middleware\HandleInertiaRequestsMiddleware;
use App\Http\Middleware\ResolveHouseholdMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
            'household' => ResolveHouseholdMiddleware::class,
            'trial.active' => EnsureTrialActiveMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // An Inertia form that hits a rate limit stays on its page with a
        // message, rather than opening the raw 429 page in a modal.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->hasHeader('X-Inertia')) {
                return;
            }

            $seconds = max(1, (int) ($e->getHeaders()['Retry-After'] ?? 60));
            $wait = $seconds < 60
                ? $seconds.' '.Str::plural('second', $seconds)
                : ($minutes = (int) ceil($seconds / 60)).' '.Str::plural('minute', $minutes);

            return back()->with('toastError', "Too many attempts. Try again in {$wait}.");
        });
    })->create();
