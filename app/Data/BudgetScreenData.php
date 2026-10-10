<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The budget screen for one month. */
class BudgetScreenData extends Data
{
    /**
     * @param  array<int, IncomeSourceData>  $income
     * @param  array<int, BudgetCategoryData>  $categories
     * @param  array<int, BudgetMonthSummaryData>  $history
     */
    public function __construct(
        public string $month,
        public string $monthLabel,
        public string $previousMonth,
        public string $nextMonth,
        public string $daysLeftLabel,
        public string $leftToSpend,
        public string $budgetedLabel,
        public string $perDay,
        public int $budgetedPence,
        public int $incomeTotalPence,
        public array $income,
        public string $incomeTotal,
        public array $categories,
        public array $history,
    ) {}
}
