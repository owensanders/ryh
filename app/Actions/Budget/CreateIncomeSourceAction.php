<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Household;
use App\Models\IncomeSource;
use App\Support\MoneyHelper;
use Carbon\CarbonImmutable;

class CreateIncomeSourceAction
{
    public function __construct(
        private readonly SetUpBudgetMonthAction $setUpMonth,
    ) {}

    /** @param  array<string, mixed>  $data  IncomeSourceRequest's validated input: label, meta, amount (pounds), is_recurring. */
    public function handle(Household $household, CarbonImmutable $month, array $data): IncomeSource
    {
        // Set the month up first, or adding to it early would keep its recurring items out.
        $this->setUpMonth->handle($household, $month);

        $income = $household->incomeSources()->create([
            'label' => $data['label'],
            'meta' => $data['meta'] ?? null,
            'amount_pence' => MoneyHelper::toPence($data['amount']),
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'month' => $month->startOfMonth(),
            'position' => (int) $household->incomeSources()->forMonth($month)->max('position') + 1,
        ]);

        // create() doesn't put database defaults (the colour) onto the instance.
        return $income->refresh();
    }
}
