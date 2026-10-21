<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Input\RecipeInputData;
use App\Enums\RecipeTagEnum;
use App\Support\CurrentHouseholdHelper;
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

    public function toData(): RecipeInputData
    {
        return new RecipeInputData(
            name: trim((string) $this->validated('name')),
            description: $this->validated('description'),
            durationLabel: $this->validated('duration_label'),
            difficulty: $this->validated('difficulty'),
            tags: array_values($this->validated('tags', [])),
            ingredients: array_values($this->validated('ingredients', [])),
            tint: (int) ($this->validated('tint') ?? 0),
            isFavourite: (bool) $this->validated('is_favourite', false),
        );
    }

    /** The list to put the new recipe's ingredients on, if one was picked. */
    public function shoppingListId(): ?int
    {
        return $this->filled('shopping_list_id') ? (int) $this->validated('shopping_list_id') : null;
    }
}
