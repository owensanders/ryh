<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Inputs\IncomeSourceInput;
use App\Models\IncomeSource;

class UpdateIncomeSourceAction
{
    public function handle(IncomeSource $income, IncomeSourceInput $input): IncomeSource
    {
        $income->update([
            'label' => $input->label,
            'meta' => $input->meta,
            'amount_pence' => $input->amountPence,
            'is_recurring' => $input->isRecurring,
        ]);

        return $income->refresh();
    }
}
