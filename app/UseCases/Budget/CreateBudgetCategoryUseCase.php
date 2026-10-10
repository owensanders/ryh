<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Contracts\Repositories\BudgetRepositoryInterface;
use App\Data\BudgetCategoryData;
use App\Data\Input\BudgetCategoryInputData;
use App\Models\Household;
use Carbon\CarbonImmutable;

class CreateBudgetCategoryUseCase
{
    public function __construct(private readonly BudgetRepositoryInterface $budget) {}

    public function execute(Household $household, CarbonImmutable $month, BudgetCategoryInputData $input): BudgetCategoryData
    {
        $category = $this->budget->createCategory($household, $month, $input);

        return BudgetCategoryData::fromModel($category, $category->budgeted_pence);
    }
}
