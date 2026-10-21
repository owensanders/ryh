<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The paywall a household reaches once its trial has lapsed without a subscription. */
class BillingRequiredScreenData extends Data
{
    public function __construct(
        public PlanData $plan,
        /** Only an Owner can subscribe; everyone else is told to ask one. */
        public bool $isOwner,
        /** A subscription exists but a payment failed: the fix is a new card, not a second subscription. */
        public bool $paymentIssue,
        /** Both Stripe prices are configured, so checkout can start. */
        public bool $billingConfigured,
    ) {}
}
