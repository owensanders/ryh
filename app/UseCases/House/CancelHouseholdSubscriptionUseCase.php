<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Models\Household;
use Stripe\Exception\InvalidRequestException;

class CancelHouseholdSubscriptionUseCase
{
    /**
     * Stops billing a household immediately — called before a household is
     * deleted, so Stripe doesn't keep charging for something that's gone.
     * Subscriptions already cancelled (including ones running out their paid
     * period) won't charge again, so they're left alone.
     */
    public function execute(Household $household): void
    {
        foreach ($household->subscriptions as $subscription) {
            if ($subscription->canceled() || in_array($subscription->stripe_status, ['canceled', 'incomplete_expired'], true)) {
                continue;
            }

            try {
                $subscription->cancelNow();
            } catch (InvalidRequestException $e) {
                // Already gone on Stripe's side — nothing left to stop.
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
            }
        }
    }
}
