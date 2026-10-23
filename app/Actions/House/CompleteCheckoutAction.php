<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class CompleteCheckoutAction
{
    /**
     * Checks with Stripe that a Checkout session the owner came back from is
     * this household's and paid. The subscription itself is recorded by
     * Cashier's `customer.subscription.created` webhook; until it lands, the
     * paywall says the payment is being confirmed instead of asking again.
     *
     * If Stripe can't be reached, the webhook still records the
     * subscription; the paywall just can't say it's on its way.
     *
     * @return bool Whether the household has paid: subscribed already, or Stripe says the session is complete.
     */
    public function handle(Household $household, string $sessionId): bool
    {
        if ($household->subscribed('default')) {
            return true;
        }

        try {
            $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);
        } catch (ApiErrorException $e) {
            Log::warning("Couldn't check Stripe checkout session {$sessionId}: {$e->getMessage()}");

            return false;
        }

        if ($session->customer !== $household->stripe_id || $session->status !== 'complete') {
            return false;
        }

        $household->markCheckoutPaid();

        return true;
    }
}
