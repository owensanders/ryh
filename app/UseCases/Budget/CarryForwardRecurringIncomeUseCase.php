<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Contracts\Repositories\BudgetRepositoryInterface;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Data\Input\IncomeSourceInputData;
use App\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CarryForwardRecurringIncomeUseCase
{
    public function __construct(
        private readonly BudgetRepositoryInterface $budget,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    public function execute(Household $household, CarbonImmutable $month): void
    {
        // Two tabs opening an empty month at once would otherwise both copy the
        // recurring income across; under the household's lock the second finds them there.
        DB::transaction(function () use ($household, $month): void {
            $this->households->lockForUpdate($household);

            if ($this->budget->incomeSourcesFor($household, $month)->isNotEmpty()) {
                return;
            }

            foreach ($this->budget->recurringIncomeSourcesBefore($household, $month) as $income) {
                $this->budget->createIncomeSource(
                    $household,
                    $month,
                    new IncomeSourceInputData($income->label, $income->meta, $income->amount_pence, isRecurring: true),
                    $income->colour,
                );
            }
        });
    }
}
