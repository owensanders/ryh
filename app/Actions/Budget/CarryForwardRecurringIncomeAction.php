<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Household;
use App\Models\IncomeSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Opening a month with no income yet copies in the nearest earlier month's recurring income. */
class CarryForwardRecurringIncomeAction
{
    public function handle(Household $household, CarbonImmutable $month): void
    {
        // Two tabs opening an empty month at once would otherwise both copy the
        // recurring income across; under the household's lock the second finds them there.
        DB::transaction(function () use ($household, $month): void {
            $household->lockRow();

            if ($household->incomeSources()->forMonth($month)->exists()) {
                return;
            }

            $latestMonth = $household->incomeSources()
                ->where('is_recurring', true)
                ->whereDate('month', '<', $month->startOfMonth()->toDateString())
                ->max('month');

            if ($latestMonth === null) {
                return;
            }

            $household->incomeSources()->where('is_recurring', true)->whereDate('month', $latestMonth)->get()
                ->each(fn (IncomeSource $income, int $index) => $income->replicate()
                    ->fill(['month' => $month->startOfMonth(), 'position' => $index + 1])
                    ->save());
        });
    }
}
