<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\BudgetCategory;
use App\Repositories\BudgetRepository;

class DeleteBudgetCategoryAction
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function handle(BudgetCategory $category): void
    {
        $this->budget->deleteCategory($category);
    }
}
