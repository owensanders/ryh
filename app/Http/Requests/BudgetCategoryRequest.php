<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Input\BudgetCategoryInputData;
use Illuminate\Foundation\Http\FormRequest;

class BudgetCategoryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60'],
            'icon' => ['nullable', 'string', 'max:8'],
            'budgeted' => ['required', 'numeric', 'min:0'],
            'is_recurring' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'label.required' => 'Give the category a name.',
        ];
    }

    public function toData(): BudgetCategoryInputData
    {
        return new BudgetCategoryInputData(
            label: trim((string) $this->validated('label')),
            icon: $this->validated('icon'),
            budgetedPence: (int) round(((float) $this->validated('budgeted')) * 100),
            isRecurring: $this->boolean('is_recurring'),
        );
    }
}
