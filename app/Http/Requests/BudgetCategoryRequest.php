<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;

class BudgetCategoryRequest extends FormRequest
{
    use NormalisesInputTrait;

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

    protected function prepareForValidation(): void
    {
        $this->merge(['label' => $this->trimmed('label')]);
    }
}
