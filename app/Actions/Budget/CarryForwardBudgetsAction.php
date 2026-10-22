<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Household;

/** The scheduled half of SetUpBudgetMonthAction: every household's current month, in its own time zone. */
class CarryForwardBudgetsAction
{
    public function __construct(
        private readonly SetUpBudgetMonthAction $setUpMonth,
    ) {}

    /** @return int How many households it looked at. */
    public function handle(): int
    {
        $count = 0;

        Household::query()->chunkById(200, function ($households) use (&$count): void {
            foreach ($households as $household) {
                $this->setUpMonth->handle($household, $household->today());
                $count++;
            }
        });

        return $count;
    }
}
