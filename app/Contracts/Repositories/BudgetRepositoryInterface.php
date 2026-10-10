<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\Input\BudgetCategoryInputData;
use App\Data\Input\IncomeSourceInputData;
use App\Enums\PaletteEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\IncomeSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface BudgetRepositoryInterface
{
    /** @return Collection<int, BudgetCategory> */
    public function categoriesFor(Household $household, CarbonImmutable $month): Collection;

    /** @return Collection<int, BudgetCategory> Categories from the given month and earlier, newest month first. */
    public function categoriesUpTo(Household $household, CarbonImmutable $month): Collection;

    public function createCategory(Household $household, CarbonImmutable $month, BudgetCategoryInputData $category, ?PaletteEnum $colour = null): BudgetCategory;

    public function updateCategory(BudgetCategory $category, BudgetCategoryInputData $changes): BudgetCategory;

    public function deleteCategory(BudgetCategory $category): void;

    /** @return Collection<int, BudgetCategory> the nearest earlier month's recurring categories, or empty if none */
    public function recurringCategoriesBefore(Household $household, CarbonImmutable $month): Collection;

    /** @return Collection<int, IncomeSource> */
    public function incomeSourcesFor(Household $household, CarbonImmutable $month): Collection;

    public function createIncomeSource(Household $household, CarbonImmutable $month, IncomeSourceInputData $income, ?PaletteEnum $colour = null): IncomeSource;

    public function updateIncomeSource(IncomeSource $income, IncomeSourceInputData $changes): IncomeSource;

    public function deleteIncomeSource(IncomeSource $income): void;

    /** @return Collection<int, IncomeSource> the nearest earlier month's recurring income, or empty if none */
    public function recurringIncomeSourcesBefore(Household $household, CarbonImmutable $month): Collection;
}
