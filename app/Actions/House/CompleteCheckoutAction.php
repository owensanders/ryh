<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Support\StripeSubscriptionSyncHelper;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use Stripe\Subscription as StripeSubscription;

class CompleteCheckoutAction
{
    public function __construct(
        private readonly StripeSubscriptionSyncHelper $syncSubscription,
    ) {}

    /**
     * Records the subscription a finished Stripe Checkout session created,
     * so the household isn't bounced back to the paywall while the
     * `customer.subscription.created` webhook is still on its way.
     *
     * @return bool Whether the household is subscribed now. False leaves it to the webhook.
     *
     * @throws ApiErrorException When Stripe can't be reached.
     */
    public function handle(Household $household, string $sessionId): bool
    {
        if ($household->subscribed('default')) {
            return true;
        }

        $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId, ['expand' => ['subscription']]);

        if ($session->customer !== $household->stripe_id || $session->status !== 'complete' || ! $session->subscription instanceof StripeSubscription) {
            return false;
        }

        $this->syncSubscription->sync($session->subscription->toArray());

        return $household->refresh()->subscribed('default');
    }
}
