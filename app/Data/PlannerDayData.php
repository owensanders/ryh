<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class PlannerDayData extends Data
{
    /** @param array<int, PlannedMealData> $meals */
    public function __construct(
        public string $date,
        public string $dayLabel,
        public string $dateLabel,
        public bool $isToday,
        public array $meals,
    ) {}
}
