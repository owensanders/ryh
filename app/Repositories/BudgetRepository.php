<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BudgetRepositoryInterface;
use App\Enums\PaletteEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\IncomeSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class BudgetRepository implements BudgetRepositoryInterface
{
    /** @return Collection<int, BudgetCategory> */
    public function categoriesFor(Household $household, CarbonImmutable $month): Collection
    {
        return $household->budgetCategories()
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->get();
    }

    /** @param array<string, mixed> $attributes */
    public function createCategory(Household $household, CarbonImmutable $month, array $attributes): BudgetCategory
    {
        $existing = $this->categoriesFor($household, $month);

        $category = $household->budgetCategories()->create([
            'colour' => $this->leastUsedColour($existing),
            ...$attributes,
            'month' => $month->startOfMonth(),
            'position' => (int) $existing->max('position') + 1,
        ]);

        // create() doesn't hydrate DB-level defaults (e.g. `colour`) onto the
        // in-memory instance, so refresh before it's used to build a DTO.
        return $category->refresh();
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

    /** @param array<string, mixed> $attributes */
    public function updateCategory(BudgetCategory $category, array $attributes): BudgetCategory
    {
        $category->update($attributes);

        return $category->refresh();
    }

    public function deleteCategory(BudgetCategory $category): void
    {
        $category->delete();
    }

    /** @return Collection<int, BudgetCategory> */
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

    /** @param array<string, mixed> $attributes */
    public function createIncomeSource(Household $household, CarbonImmutable $month, array $attributes): IncomeSource
    {
        $position = (int) $household->incomeSources()
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->max('position') + 1;

        $income = $household->incomeSources()->create([
            ...$attributes,
            'month' => $month->startOfMonth(),
            'position' => $position,
        ]);

        // create() doesn't hydrate DB-level defaults (e.g. `colour`) onto the
        // in-memory instance, so refresh before it's used to build a DTO.
        return $income->refresh();
    }

    /** @param array<string, mixed> $attributes */
    public function updateIncomeSource(IncomeSource $income, array $attributes): IncomeSource
    {
        $income->update($attributes);

        return $income->refresh();
    }

    public function deleteIncomeSource(IncomeSource $income): void
    {
        $income->delete();
    }

    /** @return Collection<int, IncomeSource> */
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
}
