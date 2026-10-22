<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class DashboardData extends Data
{
    /**
     * @param  array<int, MemberData>  $family
     * @param  array<int, ChoreData>  $chores
     * @param  array<int, AgendaGroupData>  $agenda
     */
    public function __construct(
        /** The greeting and the date are worked out in the browser, from the reader's own clock. */
        public string $firstName,
        public string $summaryLine,
        public int $streakDays,
        public array $family,
        public ?PlannedMealData $tonight,
        public ?ShoppingListData $shoppingList,
        public array $chores,
        public ChoreProgressData $choreProgress,
        public array $agenda,
        public BudgetSummaryData $budget,
    ) {}
}
