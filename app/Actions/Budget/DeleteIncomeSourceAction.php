<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\IncomeSource;
use App\Repositories\BudgetRepository;

class DeleteIncomeSourceAction
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function handle(IncomeSource $income): void
    {
        $this->budget->deleteIncomeSource($income);
    }
}
