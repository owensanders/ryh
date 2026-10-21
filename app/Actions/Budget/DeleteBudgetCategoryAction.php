<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\BudgetCategory;

class DeleteBudgetCategoryAction
{
    public function handle(BudgetCategory $category): void
    {
        $category->delete();
    }
}
