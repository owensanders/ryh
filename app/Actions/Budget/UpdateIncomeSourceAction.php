<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Data\IncomeSourceData;
use App\Data\Input\IncomeSourceInputData;
use App\Models\IncomeSource;
use App\Repositories\BudgetRepository;

class UpdateIncomeSourceAction
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function handle(IncomeSource $income, IncomeSourceInputData $changes): IncomeSourceData
    {
        return IncomeSourceData::fromModel($this->budget->updateIncomeSource($income, $changes));
    }
}
