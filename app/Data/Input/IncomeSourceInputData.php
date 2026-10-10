<?php

declare(strict_types=1);

namespace App\Data\Input;

/** An income source as the budget form submits it, validated by IncomeSourceRequest. */
final readonly class IncomeSourceInputData
{
    public function __construct(
        public string $label,
        public ?string $meta,
        public int $amountPence,
        public bool $isRecurring,
    ) {}
}
