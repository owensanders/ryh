<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RecipeTagEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecipeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('recipes', 'name')->where('household_id', $this->user()?->household_id)->ignore($this->route('recipe')),
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
                Rule::exists('shopping_lists', 'id')->where('household_id', $this->user()?->household_id),
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

    /**
     * The recipe columns, with the defaults the form leaves off.
     *
     * @return array<string, mixed>
     */
    public function recipeAttributes(): array
    {
        return [
            'name' => trim((string) $this->validated('name')),
            'description' => $this->validated('description'),
            'duration_label' => $this->validated('duration_label'),
            'difficulty' => $this->validated('difficulty'),
            'tags' => $this->validated('tags', []),
            'ingredients' => $this->validated('ingredients', []),
            'tint' => $this->validated('tint') ?? 0,
            'is_favourite' => (bool) $this->validated('is_favourite', false),
        ];
    }
}
