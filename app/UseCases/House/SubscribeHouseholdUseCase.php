<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Models\Household;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubscribeHouseholdUseCase
{
    /**
     * Starts the household's subscription to the Home plan at the given
     * billing interval. A household that's already subscribed has nothing
     * left to do here.
     *
     * @param  'monthly'|'annual'  $interval
     * @return string|null A Stripe Checkout URL to redirect to, or null when the household was already subscribed.
     */
    public function execute(Household $household, string $interval, string $successUrl, string $cancelUrl): ?string
    {
        if ($household->subscribed('default')) {
            return null;
        }

        $stripePrice = config("plans.intervals.{$interval}.stripe_price");

        if (blank($stripePrice)) {
            Log::error("No Stripe price configured for the Home plan's {$interval} interval.");

            throw new RuntimeException('Billing is not set up yet.');
        }

        $checkout = $household->newSubscription('default', $stripePrice)->checkout([
            'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'plan_checkout=success',
            'cancel_url' => $cancelUrl.(str_contains($cancelUrl, '?') ? '&' : '?').'plan_checkout=cancelled',
        ]);

        return $checkout->asStripeCheckoutSession()->url;
    }
}
