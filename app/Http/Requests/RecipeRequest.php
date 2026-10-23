<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RecipeTagEnum;
use App\Inputs\RecipeInput;
use App\Support\CurrentHouseholdHelper;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecipeRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('recipes', 'name')->where('household_id', CurrentHouseholdHelper::id($this))->ignore($this->route('recipe')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_label' => ['nullable', 'string', 'max:40'],
            'difficulty' => ['nullable', 'string', 'max:40'],
            'tags' => ['array'],
            'tags.*' => [Rule::enum(RecipeTagEnum::class)],
            'ingredients' => ['array'],
            'ingredients.*.name' => ['required', 'string', 'max:120'],
            'ingredients.*.quantity' => ['nullable', 'string', 'max:40'],
            'tint' => ['nullable', 'integer', 'min:0', 'max:4'],
            'is_favourite' => ['boolean'],
            'shopping_list_id' => [
                'nullable',
                'integer',
                Rule::exists('shopping_lists', 'id')->where('household_id', CurrentHouseholdHelper::id($this)),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the recipe a name.',
            'name.unique' => 'You already have a recipe with this name.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->trimmed('name'),
            'shopping_list_id' => $this->idOrNull('shopping_list_id'),
        ]);
    }

    public function toInput(): RecipeInput
    {
        return RecipeInput::from($this->validated());
    }
}
