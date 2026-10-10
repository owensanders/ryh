<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Input\PlannedMealInputData;
use App\Data\Input\RecipeDetailsInputData;
use App\Enums\MealSlotEnum;
use App\Enums\RecipeTagEnum;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Optional;

class PlannedMealRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recipe_id' => [
                'required_without:new_recipe_name',
                'nullable',
                'integer',
                Rule::exists('recipes', 'id')->where('household_id', $this->user()?->household_id),
            ],
            'new_recipe_name' => [
                'required_without:recipe_id',
                'nullable',
                'string',
                'max:120',
                Rule::unique('recipes', 'name')->where('household_id', $this->user()?->household_id),
            ],
            'new_recipe_description' => ['nullable', 'string', 'max:2000'],
            'new_recipe_duration_label' => ['nullable', 'string', 'max:40'],
            'new_recipe_difficulty' => ['nullable', 'string', 'max:40'],
            'new_recipe_tags' => ['array'],
            'new_recipe_tags.*' => [Rule::enum(RecipeTagEnum::class)],
            'new_recipe_ingredients' => ['array'],
            'new_recipe_ingredients.*.name' => ['required', 'string', 'max:120'],
            'new_recipe_ingredients.*.quantity' => ['nullable', 'string', 'max:40'],
            'new_recipe_tint' => ['nullable', 'integer', 'min:0', 'max:4'],
            'new_recipe_is_favourite' => ['boolean'],
            'new_recipe_shopping_list_id' => [
                'nullable',
                'integer',
                Rule::exists('shopping_lists', 'id')->where('household_id', $this->user()?->household_id),
            ],
            'planned_on' => ['required', 'date'],
            'slot' => ['required', Rule::enum(MealSlotEnum::class)],
            'cook_id' => [
                'nullable',
                'integer',
                Rule::exists('household_user', 'user_id')->where('household_id', $this->user()?->current_household_id)->where('pending', false),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'recipe_id.required_without' => 'Pick a recipe or name a new one.',
            'new_recipe_name.required_without' => 'Pick a recipe or name a new one.',
            'new_recipe_name.unique' => 'You already have a recipe with this name.',
            'planned_on.required' => 'Choose a day for this meal.',
        ];
    }

    public function toData(): PlannedMealInputData
    {
        $validated = $this->validated();
        // Recipe details are only passed on when the request sent them, so an
        // existing recipe planned by id alone keeps its details untouched.
        $sent = fn (string $key, callable $value) => array_key_exists($key, $validated) ? $value($validated[$key]) : Optional::create();

        return new PlannedMealInputData(
            recipeId: isset($validated['recipe_id']) ? (int) $validated['recipe_id'] : null,
            newRecipeName: trim((string) ($validated['new_recipe_name'] ?? '')) ?: null,
            recipeDetails: new RecipeDetailsInputData(
                description: $sent('new_recipe_description', fn (mixed $value) => trim((string) $value) ?: null),
                durationLabel: $sent('new_recipe_duration_label', fn (mixed $value) => $value),
                difficulty: $sent('new_recipe_difficulty', fn (mixed $value) => $value),
                tags: $sent('new_recipe_tags', fn (mixed $value) => array_values($value ?? [])),
                ingredients: $sent('new_recipe_ingredients', fn (mixed $value) => array_values($value ?? [])),
                tint: $sent('new_recipe_tint', fn (mixed $value) => (int) ($value ?? 0)),
                isFavourite: $sent('new_recipe_is_favourite', fn (mixed $value) => (bool) $value),
            ),
            addIngredientsToListId: isset($validated['new_recipe_shopping_list_id']) ? (int) $validated['new_recipe_shopping_list_id'] : null,
            plannedOn: CarbonImmutable::parse((string) $validated['planned_on']),
            slot: MealSlotEnum::from((string) $validated['slot']),
            cookId: isset($validated['cook_id']) ? (int) $validated['cook_id'] : null,
        );
    }
}
