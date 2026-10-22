<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Traits\ValidatesBudgetMonthTrait;
use Illuminate\Foundation\Http\FormRequest;

class SetUpBudgetMonthRequest extends FormRequest
{
    use ValidatesBudgetMonthTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'month' => $this->budgetMonthRules(required: true),
        ];
    }
}
