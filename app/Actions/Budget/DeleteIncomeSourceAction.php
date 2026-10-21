<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\IncomeSource;

class DeleteIncomeSourceAction
{
    public function handle(IncomeSource $income): void
    {
        $income->delete();
    }
}
