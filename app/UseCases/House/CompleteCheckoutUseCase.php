<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Models\Household;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Stripe\Subscription as StripeSubscription;

class CompleteCheckoutUseCase
{
    /**
     * Records the subscription a finished Stripe Checkout session created,
     * so the household isn't bounced back to the paywall while the
     * `customer.subscription.created` webhook is still on its way. Uses
     * Cashier's own webhook handler, so whichever lands first wins and the
     * other is a no-op.
     */
    public function execute(Household $household, string $sessionId): void
    {
        if ($household->subscribed('default')) {
            return;
        }

        $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId, ['expand' => ['subscription']]);

        if ($session->customer !== $household->stripe_id || $session->status !== 'complete' || ! $session->subscription instanceof StripeSubscription) {
            return;
        }

        $sync = new class extends WebhookController
        {
            /** @param  array<string, mixed>  $subscription */
            public function created(array $subscription): void
            {
                $this->handleCustomerSubscriptionCreated(['data' => ['object' => $subscription]]);
            }
        };

        $sync->created($session->subscription->toArray());
    }
}
