<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Exceptions\BillingUnavailableException;
use App\Models\Household;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class OpenBillingPortalAction
{
    /**
     * A Stripe billing portal session for the household, where the owner
     * changes card or plan, or cancels.
     *
     * @return string Where to send the owner.
     *
     * @throws BillingUnavailableException When the household has never been to Stripe, or Stripe can't be reached.
     */
    public function handle(Household $household, string $returnUrl): string
    {
        if (! $household->hasStripeId()) {
            throw BillingUnavailableException::noBillingDetails();
        }

        try {
            return $household->billingPortalUrl($returnUrl);
        } catch (ApiErrorException $e) {
            Log::error("Couldn't open the Stripe billing portal for household {$household->id}: {$e->getMessage()}");

            throw BillingUnavailableException::providerUnreachable($e);
        }
    }
}
