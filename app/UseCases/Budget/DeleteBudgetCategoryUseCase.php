<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Models\BudgetCategory;
use App\Repositories\BudgetRepository;

class DeleteBudgetCategoryUseCase
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function execute(BudgetCategory $category): void
    {
        $this->budget->deleteCategory($category);
    }
}
