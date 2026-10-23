<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Casts\PoundsToPenceCast;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** A budget category as BudgetCategoryRequest validated it. */
#[MapInputName(SnakeCaseMapper::class)]
final class BudgetCategoryInput extends Dto
{
    public function __construct(
        public readonly string $label,
        /** The form takes pounds. */
        #[MapInputName('budgeted'), WithCast(PoundsToPenceCast::class)]
        public readonly int $budgetedPence,
        public readonly ?string $icon = null,
        public readonly bool $isRecurring = false,
    ) {}
}
