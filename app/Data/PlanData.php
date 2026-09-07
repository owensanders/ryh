<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class PlanData extends Data
{
    /** @param  list<string>  $features */
    public function __construct(
        public string $name,
        public float $priceMonthly,
        public float $priceAnnual,
        public string $sub,
        public string $cta,
        public string $note,
        public array $features,
    ) {}

    public static function current(): self
    {
        return new self(
            name: config('plans.name'),
            priceMonthly: config('plans.intervals.monthly.price'),
            priceAnnual: config('plans.intervals.annual.price'),
            sub: config('plans.sub'),
            cta: config('plans.cta'),
            note: config('plans.note'),
            features: config('plans.features'),
        );
    }
}
