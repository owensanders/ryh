<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Household;
use Carbon\CarbonImmutable;

/**
 * Brings the recurring categories and income into a month that has none yet.
 * Runs from the schedule for each household's current month, when someone
 * asks for a later month early, and before anything is added to a month, so
 * an early addition never keeps the recurring ones out. Safe to repeat.
 */
class SetUpBudgetMonthAction
{
    public function __construct(
        private readonly CarryForwardRecurringCategoriesAction $carryForwardCategories,
        private readonly CarryForwardRecurringIncomeAction $carryForwardIncome,
    ) {}

    public function handle(Household $household, CarbonImmutable $month): void
    {
        $this->carryForwardCategories->handle($household, $month);
        $this->carryForwardIncome->handle($household, $month);
    }
}
