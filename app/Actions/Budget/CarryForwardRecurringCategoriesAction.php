<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Data\Input\BudgetCategoryInputData;
use App\Models\Household;
use App\Repositories\BudgetRepository;
use App\Repositories\HouseholdRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CarryForwardRecurringCategoriesAction
{
    public function __construct(
        private readonly BudgetRepository $budget,
        private readonly HouseholdRepository $households,
    ) {}

    public function handle(Household $household, CarbonImmutable $month): void
    {
        // Two tabs opening an empty month at once would otherwise both copy the
        // recurring categories across; under the household's lock the second finds them there.
        DB::transaction(function () use ($household, $month): void {
            $this->households->lockForUpdate($household);

            if ($this->budget->categoriesFor($household, $month)->isNotEmpty()) {
                return;
            }

            foreach ($this->budget->recurringCategoriesBefore($household, $month) as $category) {
                $this->budget->createCategory(
                    $household,
                    $month,
                    new BudgetCategoryInputData($category->label, $category->icon, $category->budgeted_pence, isRecurring: true),
                    $category->colour,
                );
            }
        });
    }
}
