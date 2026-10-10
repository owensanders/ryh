<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Input\ShoppingListInputData;
use App\Enums\PaletteEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShoppingListRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'colour' => ['nullable', Rule::enum(PaletteEnum::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the list a name.',
        ];
    }

    public function toData(): ShoppingListInputData
    {
        return new ShoppingListInputData(
            name: trim((string) $this->validated('name')),
            colour: PaletteEnum::tryFrom((string) $this->validated('colour')) ?? PaletteEnum::Mint,
        );
    }
}
