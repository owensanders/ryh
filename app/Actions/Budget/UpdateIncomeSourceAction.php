<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\IncomeSource;
use App\Support\MoneyHelper;

class UpdateIncomeSourceAction
{
    /** @param  array<string, mixed>  $data  IncomeSourceRequest's validated input; see CreateIncomeSourceAction. */
    public function handle(IncomeSource $income, array $data): IncomeSource
    {
        $income->update([
            'label' => $data['label'],
            'meta' => $data['meta'] ?? null,
            'amount_pence' => MoneyHelper::toPence($data['amount']),
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
        ]);

        return $income->refresh();
    }
}
