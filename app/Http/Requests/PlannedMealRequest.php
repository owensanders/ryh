<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MealSlotEnum;
use App\Enums\RecipeTagEnum;
use App\Inputs\PlannedMealInput;
use App\Support\CurrentHouseholdHelper;
use App\Traits\NormalisesInputTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlannedMealRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recipe_id' => [
                'required_without:new_recipe_name',
                'nullable',
                'integer',
                Rule::exists('recipes', 'id')->where('household_id', CurrentHouseholdHelper::id($this)),
            ],
            'new_recipe_name' => [
                'required_without:recipe_id',
                'nullable',
                'string',
                'max:120',
                Rule::unique('recipes', 'name')->where('household_id', CurrentHouseholdHelper::id($this)),
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
                Rule::exists('shopping_lists', 'id')->where('household_id', CurrentHouseholdHelper::id($this)),
            ],
            'planned_on' => ['required', 'date'],
            'slot' => ['required', Rule::enum(MealSlotEnum::class)],
            'cook_id' => [
                'nullable',
                'integer',
                Rule::exists('household_user', 'user_id')->where('household_id', CurrentHouseholdHelper::id($this))->where('pending', false),
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

    /**
     * The new_recipe_* details are left out when they weren't sent, so an
     * existing recipe planned by id alone keeps its details untouched
     * (ResolvePlannedRecipeAction checks which keys are there).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipe_id' => $this->idOrNull('recipe_id'),
            'new_recipe_name' => $this->trimmedOrNull('new_recipe_name'),
            'new_recipe_shopping_list_id' => $this->idOrNull('new_recipe_shopping_list_id'),
            'cook_id' => $this->idOrNull('cook_id'),
        ]);

        if ($this->has('new_recipe_description')) {
            $this->merge(['new_recipe_description' => $this->trimmedOrNull('new_recipe_description')]);
        }
    }

    public function toInput(): PlannedMealInput
    {
        return PlannedMealInput::from($this->validated());
    }
}
