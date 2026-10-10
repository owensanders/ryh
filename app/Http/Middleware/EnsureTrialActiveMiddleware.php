<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Traits\ResolvesHouseholdTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrialActiveMiddleware
{
    use ResolvesHouseholdTrait;

    /**
     * Once a household's trial has lapsed with no subscription, nothing in
     * the app is reachable except the billing paywall — this runs after
     * `household` resolves the current household, and before every gated
     * route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->household($request)->needsToSubscribe()) {
            return redirect()->route('billing.required');
        }

        return $next($request);
    }
}
