<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Data\IncomeSourceData;
use App\Data\Input\IncomeSourceInputData;
use App\Models\Household;
use App\Repositories\BudgetRepository;
use Carbon\CarbonImmutable;

class CreateIncomeSourceUseCase
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function execute(Household $household, CarbonImmutable $month, IncomeSourceInputData $input): IncomeSourceData
    {
        return IncomeSourceData::fromModel($this->budget->createIncomeSource($household, $month, $input));
    }
}
