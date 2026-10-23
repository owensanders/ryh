<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
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
     * @return bool Whether the household has paid: subscribed already, or Stripe says the session is complete.
     *
     * @throws ApiErrorException When Stripe can't be reached.
     */
    public function handle(Household $household, string $sessionId): bool
    {
        if ($household->subscribed('default')) {
            return true;
        }

        $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);

        if ($session->customer !== $household->stripe_id || $session->status !== 'complete') {
            return false;
        }

        $household->markCheckoutPaid();

        return true;
    }
}
