<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Enums\PaletteEnum;
use App\Inputs\BudgetCategoryInput;
use App\Models\BudgetCategory;
use App\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CreateBudgetCategoryAction
{
    public function __construct(
        private readonly SetUpBudgetMonthAction $setUpMonth,
    ) {}

    public function handle(Household $household, CarbonImmutable $month, BudgetCategoryInput $input): BudgetCategory
    {
        // Set the month up first, or adding to it early would keep its recurring items out.
        $this->setUpMonth->handle($household, $month);

        $existing = $household->budgetCategories()->forMonth($month)->get();

        $category = $household->budgetCategories()->create([
            'label' => $input->label,
            'icon' => $input->icon,
            'budgeted_pence' => $input->budgetedPence,
            'is_recurring' => $input->isRecurring,
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
