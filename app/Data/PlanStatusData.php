<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Household;
use Spatie\LaravelData\Data;

class PlanStatusData extends Data
{
    public function __construct(
        public bool $onTrial,
        public ?string $trialEndsLabel,
        public bool $subscribed,
        /** 'monthly' | 'annual' while subscribed, otherwise null. */
        public ?string $interval,
        /** Set once a subscription is cancelled but still running to the end of its paid period. */
        public ?string $endsLabel,
        /** A renewal failed — the owner needs to update their card rather than subscribe again. */
        public bool $paymentIssue,
        public float $priceMonthly,
        public float $priceAnnual,
    ) {}

    public static function fromHousehold(Household $household): self
    {
        $subscription = $household->subscription('default');
        $subscribed = $household->subscribed('default');
        // Unset when Stripe isn't configured (local/e2e) — then nothing can be annual.
        $annualPrice = config('plans.intervals.annual.stripe_price');

        return new self(
            onTrial: $household->isOnTrial(),
            trialEndsLabel: $household->isOnTrial() ? $household->trial_ends_at->format('j F Y') : null,
            subscribed: $subscribed,
            interval: match (true) {
                ! $subscribed => null,
                is_string($annualPrice) && $subscription->hasPrice($annualPrice) => 'annual',
                default => 'monthly',
            },
            endsLabel: $subscription?->onGracePeriod() ? $subscription->ends_at->format('j F Y') : null,
            paymentIssue: $household->hasPaymentIssue(),
            priceMonthly: config('plans.intervals.monthly.price'),
            priceAnnual: config('plans.intervals.annual.price'),
        );
    }
}
