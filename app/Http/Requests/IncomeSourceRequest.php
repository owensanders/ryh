<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Input\IncomeSourceInputData;
use Illuminate\Foundation\Http\FormRequest;

class IncomeSourceRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
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

    public function toData(): IncomeSourceInputData
    {
        return new IncomeSourceInputData(
            label: trim((string) $this->validated('label')),
            meta: $this->validated('meta'),
            amountPence: (int) round(((float) $this->validated('amount')) * 100),
            isRecurring: $this->boolean('is_recurring'),
        );
    }
}
