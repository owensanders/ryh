<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\CurrentHouseholdHelper;
use Carbon\CarbonImmutable;
use Closure;

/**
 * The `month` a budget screen or write is about (`YYYY-MM`), kept to a sensible
 * window around the household's today, so a stray link can't open or fill
 * the year 2099.
 */
trait ValidatesBudgetMonthTrait
{
    private const int BUDGET_YEARS_BACK = 5;

    private const int BUDGET_YEARS_AHEAD = 2;

    /** The first of the month asked for, or of the household's current month when none was. */
    public function month(): CarbonImmutable
    {
        $month = $this->validated('month');
        $requested = is_string($month) ? CarbonImmutable::createFromFormat('!Y-m', $month) : null;

        return ($requested ?? CurrentHouseholdHelper::household($this)->today())->startOfMonth();
    }

    /** @return list<mixed> */
    protected function budgetMonthRules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'date_format:Y-m', $this->withinBudgetWindow(...)];
    }

    private function withinBudgetWindow(string $attribute, mixed $value, Closure $fail): void
    {
        $month = is_string($value) ? CarbonImmutable::createFromFormat('!Y-m', $value) : null;

        if ($month === null) {
            return;
        }

        $thisMonth = CurrentHouseholdHelper::household($this)->today()->startOfMonth();

        if ($month->lt($thisMonth->subYears(self::BUDGET_YEARS_BACK)) || $month->gt($thisMonth->addYears(self::BUDGET_YEARS_AHEAD))) {
            $fail('Pick a month within '.self::BUDGET_YEARS_BACK.' years back or '.self::BUDGET_YEARS_AHEAD.' ahead.');
        }
    }
}
