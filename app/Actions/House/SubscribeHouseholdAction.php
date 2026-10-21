<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Exceptions\BillingUnavailableException;
use App\Models\Household;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class SubscribeHouseholdAction
{
    /**
     * Starts the household's subscription to the Home plan at the given
     * billing interval. A household that's already subscribed has nothing
     * left to do here.
     *
     * @param  'monthly'|'annual'  $interval
     *
     * @return string|null A Stripe Checkout URL to redirect to (or the billing portal, when an existing subscription just needs a new card), or null when the household was already subscribed.
     */
    public function handle(Household $household, string $interval, string $successUrl, string $cancelUrl): ?string
    {
        if ($household->subscribed('default')) {
            return null;
        }

        $stripePrice = config("plans.intervals.{$interval}.stripe_price");

        if (blank($stripePrice)) {
            Log::error("No Stripe price configured for the Home plan's {$interval} interval.");

            throw BillingUnavailableException::notConfigured();
        }

        try {
            if ($household->hasPaymentIssue()) {
                return $household->billingPortalUrl($cancelUrl);
            }

            $checkout = $household->newSubscription('default', $stripePrice)->checkout([
                // Stripe fills in {CHECKOUT_SESSION_ID} itself, so it can't go through url encoding.
                'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl.(str_contains($cancelUrl, '?') ? '&' : '?').'plan_checkout=cancelled',
            ]);

            return $checkout->asStripeCheckoutSession()->url;
        } catch (ApiErrorException $e) {
            Log::error("Couldn't start Stripe checkout for household {$household->id}: {$e->getMessage()}");

            throw BillingUnavailableException::providerUnreachable($e);
        }
    }
}
