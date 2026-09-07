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
        public float $priceMonthly,
        public float $priceAnnual,
    ) {}

    public static function fromHousehold(Household $household): self
    {
        return new self(
            onTrial: $household->isOnTrial(),
            trialEndsLabel: $household->isOnTrial() ? $household->trial_ends_at->format('j F Y') : null,
            subscribed: $household->subscribed('default'),
            priceMonthly: config('plans.intervals.monthly.price'),
            priceAnnual: config('plans.intervals.annual.price'),
        );
    }
}
