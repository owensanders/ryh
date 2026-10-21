<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Data\IncomeSourceData;
use App\Data\Input\IncomeSourceInputData;
use App\Models\Household;
use App\Repositories\BudgetRepository;
use Carbon\CarbonImmutable;

class CreateIncomeSourceAction
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function handle(Household $household, CarbonImmutable $month, IncomeSourceInputData $input): IncomeSourceData
    {
        return IncomeSourceData::fromModel($this->budget->createIncomeSource($household, $month, $input));
    }
}
