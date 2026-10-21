<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Models\IncomeSource;
use App\Repositories\BudgetRepository;

class DeleteIncomeSourceUseCase
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function execute(IncomeSource $income): void
    {
        $this->budget->deleteIncomeSource($income);
    }
}
