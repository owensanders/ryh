<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\BudgetCategory;
use App\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Opening a month with no categories yet copies in the nearest earlier month's recurring ones. */
class CarryForwardRecurringCategoriesAction
{
    public function handle(Household $household, CarbonImmutable $month): void
    {
        // Two tabs opening an empty month at once would otherwise both copy the
        // recurring categories across; under the household's lock the second finds them there.
        DB::transaction(function () use ($household, $month): void {
            $household->lockRow();

            if ($household->budgetCategories()->forMonth($month)->exists()) {
                return;
            }

            $latestMonth = $household->budgetCategories()
                ->where('is_recurring', true)
                ->whereDate('month', '<', $month->startOfMonth()->toDateString())
                ->max('month');

            if ($latestMonth === null) {
                return;
            }

            $household->budgetCategories()->where('is_recurring', true)->whereDate('month', $latestMonth)->get()
                ->each(fn (BudgetCategory $category, int $index) => $category->replicate()
                    ->fill(['month' => $month->startOfMonth(), 'position' => $index + 1])
                    ->save());
        });
    }
}
