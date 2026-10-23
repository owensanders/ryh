<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Inputs\IncomeSourceInput;
use App\Models\Household;
use App\Models\IncomeSource;
use Carbon\CarbonImmutable;

class CreateIncomeSourceAction
{
    public function __construct(
        private readonly SetUpBudgetMonthAction $setUpMonth,
    ) {}

    public function handle(Household $household, CarbonImmutable $month, IncomeSourceInput $input): IncomeSource
    {
        // Set the month up first, or adding to it early would keep its recurring items out.
        $this->setUpMonth->handle($household, $month);

        $income = $household->incomeSources()->create([
            'label' => $input->label,
            'meta' => $input->meta,
            'amount_pence' => $input->amountPence,
            'is_recurring' => $input->isRecurring,
            'month' => $month->startOfMonth(),
            'position' => (int) $household->incomeSources()->forMonth($month)->max('position') + 1,
        ]);

        // create() doesn't put database defaults (the colour) onto the instance.
        return $income->refresh();
    }
}
