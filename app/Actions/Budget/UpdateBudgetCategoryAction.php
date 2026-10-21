<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\BudgetCategory;
use App\Support\MoneyHelper;

class UpdateBudgetCategoryAction
{
    /** @param  array<string, mixed>  $data  BudgetCategoryRequest's validated input; see CreateBudgetCategoryAction. */
    public function handle(BudgetCategory $category, array $data): BudgetCategory
    {
        $category->update([
            'label' => $data['label'],
            'icon' => $data['icon'] ?? null,
            'budgeted_pence' => MoneyHelper::toPence($data['budgeted']),
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
        ]);

        return $category->refresh();
    }
}
