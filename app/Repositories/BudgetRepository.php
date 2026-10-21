<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Input\BudgetCategoryInputData;
use App\Data\Input\IncomeSourceInputData;
use App\Enums\PaletteEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\IncomeSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class BudgetRepository
{
    /** @return Collection<int, BudgetCategory> */
    public function categoriesFor(Household $household, CarbonImmutable $month): Collection
    {
        return $household->budgetCategories()
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->get();
    }

    /** @return Collection<int, BudgetCategory> Categories from the given month and earlier, newest month first. */
    public function categoriesUpTo(Household $household, CarbonImmutable $month): Collection
    {
        return $household->budgetCategories()
            ->whereDate('month', '<=', $month->startOfMonth()->toDateString())
            ->orderByDesc('month')
            ->get();
    }

    public function createCategory(Household $household, CarbonImmutable $month, BudgetCategoryInputData $category, ?PaletteEnum $colour = null): BudgetCategory
    {
        $existing = $this->categoriesFor($household, $month);

        $created = $household->budgetCategories()->create([
            'colour' => $colour ?? $this->leastUsedColour($existing),
            ...$this->categoryColumns($category),
            'month' => $month->startOfMonth(),
            'position' => (int) $existing->max('position') + 1,
        ]);

        // create() doesn't hydrate DB-level defaults (e.g. `colour`) onto the
        // in-memory instance, so refresh before it's used to build a DTO.
        return $created->refresh();
    }

    /**
     * The palette colour fewest of the month's categories use (palette order
     * breaks ties), so each new category stands apart from the others.
     *
     * @param  Collection<int, BudgetCategory>  $categories
     */
    private function leastUsedColour(Collection $categories): PaletteEnum
    {
        $counts = $categories->countBy(fn (BudgetCategory $category) => $category->colour->value);

        return collect(PaletteEnum::cases())
            ->sortBy(fn (PaletteEnum $colour, int $index) => [$counts->get($colour->value, 0), $index])
            ->firstOrFail();
    }

    public function updateCategory(BudgetCategory $category, BudgetCategoryInputData $changes): BudgetCategory
    {
        $category->update($this->categoryColumns($changes));

        return $category->refresh();
    }

    public function deleteCategory(BudgetCategory $category): void
    {
        $category->delete();
    }

    /** @return Collection<int, BudgetCategory> the nearest earlier month's recurring categories, or empty if none */
    public function recurringCategoriesBefore(Household $household, CarbonImmutable $month): Collection
    {
        $latestMonth = $household->budgetCategories()
            ->where('is_recurring', true)
            ->whereDate('month', '<', $month->startOfMonth()->toDateString())
            ->max('month');

        if ($latestMonth === null) {
            return collect();
        }

        return $household->budgetCategories()
            ->where('is_recurring', true)
            ->whereDate('month', $latestMonth)
            ->get();
    }

    /** @return Collection<int, IncomeSource> */
    public function incomeSourcesFor(Household $household, CarbonImmutable $month): Collection
    {
        return $household->incomeSources()
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->get();
    }

    public function createIncomeSource(Household $household, CarbonImmutable $month, IncomeSourceInputData $income, ?PaletteEnum $colour = null): IncomeSource
    {
        $position = (int) $household->incomeSources()
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->max('position') + 1;

        $created = $household->incomeSources()->create([
            ...$this->incomeColumns($income),
            ...($colour !== null ? ['colour' => $colour] : []),
            'month' => $month->startOfMonth(),
            'position' => $position,
        ]);

        // create() doesn't hydrate DB-level defaults (e.g. `colour`) onto the
        // in-memory instance, so refresh before it's used to build a DTO.
        return $created->refresh();
    }

    public function updateIncomeSource(IncomeSource $income, IncomeSourceInputData $changes): IncomeSource
    {
        $income->update($this->incomeColumns($changes));

        return $income->refresh();
    }

    public function deleteIncomeSource(IncomeSource $income): void
    {
        $income->delete();
    }

    /** @return Collection<int, IncomeSource> the nearest earlier month's recurring income, or empty if none */
    public function recurringIncomeSourcesBefore(Household $household, CarbonImmutable $month): Collection
    {
        $latestMonth = $household->incomeSources()
            ->where('is_recurring', true)
            ->whereDate('month', '<', $month->startOfMonth()->toDateString())
            ->max('month');

        if ($latestMonth === null) {
            return collect();
        }

        return $household->incomeSources()
            ->where('is_recurring', true)
            ->whereDate('month', $latestMonth)
            ->get();
    }

    /** @return array<string, mixed> */
    private function categoryColumns(BudgetCategoryInputData $category): array
    {
        return [
            'label' => $category->label,
            'icon' => $category->icon,
            'budgeted_pence' => $category->budgetedPence,
            'is_recurring' => $category->isRecurring,
        ];
    }

    /** @return array<string, mixed> */
    private function incomeColumns(IncomeSourceInputData $income): array
    {
        return [
            'label' => $income->label,
            'meta' => $income->meta,
            'amount_pence' => $income->amountPence,
            'is_recurring' => $income->isRecurring,
        ];
    }
}
