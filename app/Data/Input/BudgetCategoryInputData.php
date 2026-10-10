<?php

declare(strict_types=1);

namespace App\Data\Input;

/** A spending category as the budget form submits it, validated by BudgetCategoryRequest. */
final readonly class BudgetCategoryInputData
{
    public function __construct(
        public string $label,
        public ?string $icon,
        public int $budgetedPence,
        public bool $isRecurring,
    ) {}
}
