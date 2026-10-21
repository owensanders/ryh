<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Enums\PaletteEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Support\MoneyHelper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CreateBudgetCategoryAction
{
    /** @param  array<string, mixed>  $data  BudgetCategoryRequest's validated input: label, icon, budgeted (pounds), is_recurring. */
    public function handle(Household $household, CarbonImmutable $month, array $data): BudgetCategory
    {
        $existing = $household->budgetCategories()->forMonth($month)->get();

        $category = $household->budgetCategories()->create([
            'label' => $data['label'],
            'icon' => $data['icon'] ?? null,
            'budgeted_pence' => MoneyHelper::toPence($data['budgeted']),
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'colour' => $this->leastUsedColour($existing),
            'month' => $month->startOfMonth(),
            'position' => (int) $existing->max('position') + 1,
        ]);

        // create() doesn't put database defaults onto the instance.
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
}
