<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequireStripeWebhookSecretMiddleware
{
    /**
     * Cashier only verifies webhook signatures when a secret is configured,
     * so without one anyone could post a fake "subscription created" event.
     * Refuse instead — Stripe retries failed deliveries for three days, so
     * nothing is lost once the secret is set.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (blank(config('cashier.webhook.secret'))) {
            Log::error('Rejected a Stripe webhook because STRIPE_WEBHOOK_SECRET is not set.');

            abort(503, 'Webhook signing secret not configured.');
        }

        return $next($request);
    }
}
