<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\IncomeSource;
use App\Support\MoneyHelper;
use Spatie\LaravelData\Data;

class IncomeSourceData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public ?string $meta,
        public string $colour,
        public string $amount,
        public int $amountPence,
        public string $initials,
        public bool $isRecurring,
    ) {}

    public static function fromModel(IncomeSource $income): self
    {
        return new self(
            id: $income->id,
            label: $income->label,
            meta: $income->meta,
            colour: $income->colour->cssVar(),
            amount: MoneyHelper::format($income->amount_pence),
            amountPence: $income->amount_pence,
            initials: mb_strtoupper(mb_substr($income->label, 0, 2)),
            isRecurring: $income->is_recurring,
        );
    }
}
