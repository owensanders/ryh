<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Contracts\Repositories\BudgetRepositoryInterface;
use App\Data\BudgetCategoryData;
use App\Data\BudgetMonthSummaryData;
use App\Data\IncomeSourceData;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Support\Money;
use Carbon\CarbonImmutable;

class GetBudgetScreenUseCase
{
    private const HISTORY_MONTHS = 3;

    public function __construct(
        private readonly BudgetRepositoryInterface $budget,
        private readonly CarryForwardRecurringCategoriesUseCase $carryForwardCategories,
        private readonly CarryForwardRecurringIncomeUseCase $carryForwardIncome,
    ) {}

    /**
     * @return array{
     *     month: string, monthLabel: string, previousMonth: string, nextMonth: string,
     *     daysLeftLabel: string, leftToSpend: string, budgetedLabel: string, perDay: string,
     *     budgetedPence: int, incomeTotalPence: int,
     *     income: list<IncomeSourceData>, incomeTotal: string,
     *     categories: list<BudgetCategoryData>, history: list<BudgetMonthSummaryData>,
     * }
     */
    public function execute(Household $household, CarbonImmutable $month): array
    {
        $today = CarbonImmutable::today();
        $first = $month->startOfMonth();

        $this->carryForwardCategories->execute($household, $first);
        $this->carryForwardIncome->execute($household, $first);

        $categories = $this->budget->categoriesFor($household, $first);
        $income = $this->budget->incomeSourcesFor($household, $first);

        $budgeted = (int) $categories->sum('budgeted_pence');
        $incomeTotal = (int) $income->sum('amount_pence');
        $leftToSpend = $incomeTotal - $budgeted;

        $daysLeft = $first->isSameMonth($today)
            ? (int) $today->diffInDays($first->endOfMonth(), absolute: true)
            : $first->daysInMonth;

        return [
            'month' => $first->format('Y-m'),
            'monthLabel' => $first->format('F Y'),
            'previousMonth' => $first->subMonth()->format('Y-m'),
            'nextMonth' => $first->addMonth()->format('Y-m'),
            'daysLeftLabel' => $daysLeft.' '.($daysLeft === 1 ? 'day' : 'days').' left',
            'leftToSpend' => Money::format($leftToSpend),
            'budgetedLabel' => Money::format($budgeted),
            'perDay' => Money::format($daysLeft > 0 ? intdiv(max($leftToSpend, 0), $daysLeft) : 0),
            'budgetedPence' => $budgeted,
            'incomeTotalPence' => $incomeTotal,
            'income' => $income->map(IncomeSourceData::fromModel(...))->values()->all(),
            'incomeTotal' => Money::format($incomeTotal),
            'categories' => $categories->map(fn (BudgetCategory $category) => BudgetCategoryData::fromModel($category, $budgeted))->values()->all(),
            'history' => $this->history($household, $first),
        ];
    }

    /** @return list<BudgetMonthSummaryData> */
    private function history(Household $household, CarbonImmutable $first): array
    {
        $months = [];

        for ($i = self::HISTORY_MONTHS - 1; $i >= 0; $i--) {
            $cursor = $first->subMonths($i);
            $total = (int) $this->budget->categoriesFor($household, $cursor)->sum('budgeted_pence');

            $months[] = new BudgetMonthSummaryData(
                month: $cursor->format('Y-m'),
                monthLabel: $cursor->format('M'),
                total: Money::format($total),
                totalPence: $total,
            );
        }

        return $months;
    }
}
