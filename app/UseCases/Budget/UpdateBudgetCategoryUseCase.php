<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Data\BudgetCategoryData;
use App\Data\Input\BudgetCategoryInputData;
use App\Models\BudgetCategory;
use App\Repositories\BudgetRepository;

class UpdateBudgetCategoryUseCase
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function execute(BudgetCategory $category, BudgetCategoryInputData $changes): BudgetCategoryData
    {
        $updated = $this->budget->updateCategory($category, $changes);

        return BudgetCategoryData::fromModel($updated, $updated->budgeted_pence);
    }
}
