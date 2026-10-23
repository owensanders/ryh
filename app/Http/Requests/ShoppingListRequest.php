<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaletteEnum;
use App\Inputs\ShoppingListInput;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShoppingListRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'colour' => ['required', Rule::enum(PaletteEnum::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the list a name.',
        ];
    }

    /** A trimmed name, and mint when no colour is picked. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->trimmed('name'),
            'colour' => $this->filled('colour') ? $this->input('colour') : PaletteEnum::Mint->value,
        ]);
    }

    public function toInput(): ShoppingListInput
    {
        return ShoppingListInput::from($this->validated());
    }
}
