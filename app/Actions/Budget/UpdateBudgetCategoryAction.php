<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Inputs\BudgetCategoryInput;
use App\Models\BudgetCategory;

class UpdateBudgetCategoryAction
{
    public function handle(BudgetCategory $category, BudgetCategoryInput $input): BudgetCategory
    {
        $category->update([
            'label' => $input->label,
            'icon' => $input->icon,
            'budgeted_pence' => $input->budgetedPence,
            'is_recurring' => $input->isRecurring,
        ]);

        return $category->refresh();
    }
}
