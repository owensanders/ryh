<?php

declare(strict_types=1);

namespace App\Support;

use Laravel\Cashier\Http\Controllers\WebhookController;

/**
 * Records a Stripe subscription exactly as Cashier's `customer.subscription.created`
 * webhook would, by running Cashier's own handler, so the checkout return
 * and the webhook can never record it differently. Whichever lands first
 * creates it; Cashier skips one it already has.
 */
final class StripeSubscriptionSyncHelper extends WebhookController
{
    /** @param  array<string, mixed>  $subscription  A Stripe subscription object, as an array. */
    public function sync(array $subscription): void
    {
        $this->handleCustomerSubscriptionCreated(['data' => ['object' => $subscription]]);
    }
}
