<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class BudgetSummaryData extends Data
{
    /** @param array<int, BudgetCategoryData> $categories */
    public function __construct(
        public string $monthLabel,
        public string $budgeted,
        public int $daysLeft,
        public array $categories,
    ) {}
}
