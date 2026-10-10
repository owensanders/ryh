<?php

declare(strict_types=1);

namespace App\UseCases\Budget;

use App\Contracts\Repositories\BudgetRepositoryInterface;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CarryForwardRecurringCategoriesUseCase
{
    public function __construct(
        private readonly BudgetRepositoryInterface $budget,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    public function execute(Household $household, CarbonImmutable $month): void
    {
        // Two tabs opening an empty month at once would otherwise both copy the
        // recurring categories across; under the household's lock the second finds them there.
        DB::transaction(function () use ($household, $month): void {
            $this->households->lockForUpdate($household);

            if ($this->budget->categoriesFor($household, $month)->isNotEmpty()) {
                return;
            }

            foreach ($this->budget->recurringCategoriesBefore($household, $month) as $category) {
                $this->budget->createCategory($household, $month, [
                    'label' => $category->label,
                    'icon' => $category->icon,
                    'colour' => $category->colour,
                    'budgeted_pence' => $category->budgeted_pence,
                    'is_recurring' => true,
                ]);
            }
        });
    }
}
