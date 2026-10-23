<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Inputs\IncomeSourceInput;
use App\Traits\NormalisesInputTrait;
use App\Traits\ValidatesBudgetMonthTrait;
use Illuminate\Foundation\Http\FormRequest;

class IncomeSourceRequest extends FormRequest
{
    use ValidatesBudgetMonthTrait;
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // On a create, the month it's for (a query parameter); unused on an update.
            'month' => $this->budgetMonthRules(),
            'label' => ['required', 'string', 'max:60'],
            'meta' => ['nullable', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_recurring' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'label.required' => 'Give the income source a name.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['label' => $this->trimmed('label')]);
    }

    public function toInput(): IncomeSourceInput
    {
        return IncomeSourceInput::from($this->validated());
    }
}
