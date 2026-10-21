<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Data\IncomeSourceData;
use App\Data\Input\IncomeSourceInputData;
use App\Models\IncomeSource;
use App\Repositories\BudgetRepository;

class UpdateIncomeSourceUseCase
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function execute(IncomeSource $income, IncomeSourceInputData $changes): IncomeSourceData
    {
        return IncomeSourceData::fromModel($this->budget->updateIncomeSource($income, $changes));
    }
}
