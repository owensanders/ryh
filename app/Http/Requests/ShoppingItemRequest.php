<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Inputs\ShoppingItemInput;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;

class ShoppingItemRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'quantity' => ['nullable', 'string', 'max:40'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the item a name.',
        ];
    }

    /** Validated as it'll be stored: a trimmed name, and no quantity as null. */
    protected function prepareForValidation(): void
    {
        $this->merge(['name' => $this->trimmed('name'), 'quantity' => $this->trimmedOrNull('quantity')]);
    }

    public function toInput(): ShoppingItemInput
    {
        return ShoppingItemInput::from($this->validated());
    }
}
