<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Exceptions\BillingUnavailableException;
use App\Models\Household;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class SubscribeHouseholdAction
{
    private const int LOCK_SECONDS = 20;

    private const int WAIT_SECONDS = 15;

    /** A Checkout session lasts 24 hours; reusing it for a while covers a double-click or Back. */
    private const int REUSE_MINUTES = 30;

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
        $stripePrice = config("plans.intervals.{$interval}.stripe_price");

        if (blank($stripePrice)) {
            Log::error("No Stripe price configured for the Home plan's {$interval} interval.");

            throw BillingUnavailableException::notConfigured();
        }

        // One at a time per household: a double-click, a second tab or a second
        // owner waits here, then reuses the first's Checkout session below.
        try {
            return Cache::lock("subscribe:{$household->id}", self::LOCK_SECONDS)
                ->block(self::WAIT_SECONDS, fn (): ?string => $this->checkoutUrl($household->refresh(), $stripePrice, $successUrl, $cancelUrl));
        } catch (LockTimeoutException $e) {
            throw BillingUnavailableException::providerUnreachable($e);
        }
    }

    /**
     * The remembered session's URL, if Stripe still has it open. One that's
     * been paid, or expired, would show the owner a dead page.
     *
     * @throws ApiErrorException
     */
    private function reusableCheckoutUrl(mixed $remembered): ?string
    {
        if (! is_array($remembered) || ! is_string($remembered['id'] ?? null) || ! is_string($remembered['url'] ?? null)) {
            return null;
        }

        $session = Cashier::stripe()->checkout->sessions->retrieve($remembered['id']);

        return $session->status === 'open' ? $remembered['url'] : null;
    }

    private function checkoutUrl(Household $household, string $stripePrice, string $successUrl, string $cancelUrl): ?string
    {
        if ($household->subscribed('default')) {
            return null;
        }

        $reuseKey = "subscribe:{$household->id}:checkout:".sha1("{$stripePrice}|{$successUrl}|{$cancelUrl}");

        try {
            if ($household->hasPaymentIssue()) {
                return $household->billingPortalUrl($cancelUrl);
            }

            $reusable = $this->reusableCheckoutUrl(Cache::get($reuseKey));

            if ($reusable !== null) {
                return $reusable;
            }

            // Stripe returns the same customer for a repeat of this key, so two
            // first-time subscribes can never leave the household with two.
            $household->createOrGetStripeCustomer(requestOptions: [
                'idempotency_key' => "household-{$household->id}-customer-".sha1((string) $household->stripeName()),
            ]);

            $checkout = $household->newSubscription('default', $stripePrice)->checkout([
                // Stripe fills in {CHECKOUT_SESSION_ID} itself, so it can't go through url encoding.
                'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl.(str_contains($cancelUrl, '?') ? '&' : '?').'plan_checkout=cancelled',
            ]);

            $session = $checkout->asStripeCheckoutSession();
            Cache::put($reuseKey, ['id' => $session->id, 'url' => $session->url], now()->addMinutes(self::REUSE_MINUTES));

            return $session->url;
        } catch (ApiErrorException $e) {
            Log::error("Couldn't start Stripe checkout for household {$household->id}: {$e->getMessage()}");

            throw BillingUnavailableException::providerUnreachable($e);
        }
    }
}
