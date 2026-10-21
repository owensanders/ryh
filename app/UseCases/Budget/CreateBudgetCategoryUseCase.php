<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Data\BudgetCategoryData;
use App\Data\Input\BudgetCategoryInputData;
use App\Models\Household;
use App\Repositories\BudgetRepository;
use Carbon\CarbonImmutable;

class CreateBudgetCategoryUseCase
{
    public function __construct(private readonly BudgetRepository $budget) {}

    public function execute(Household $household, CarbonImmutable $month, BudgetCategoryInputData $input): BudgetCategoryData
    {
        $category = $this->budget->createCategory($household, $month, $input);

        return BudgetCategoryData::fromModel($category, $category->budgeted_pence);
    }
}
