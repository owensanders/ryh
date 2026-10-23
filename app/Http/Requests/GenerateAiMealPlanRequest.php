<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CookingConfidenceEnum;
use App\Enums\KitchenEquipmentEnum;
use App\Enums\MealDietEnum;
use App\Enums\MealGoalEnum;
use App\Inputs\AiMealPlanAnswersInput;
use App\Traits\NormalisesInputTrait;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The AI meal plan form. Its free text goes to the AI as data, so it's kept
 * to plain, short lines (MealPlanAgent::message()).
 */
class GenerateAiMealPlanRequest extends FormRequest
{
    use NormalisesInputTrait;

    /** "Avoid" and "use up" are comma lists of ingredients: this many, each this long. */
    private const int LIST_ITEMS = 15;

    private const int LIST_ITEM_LENGTH = 40;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'people' => ['required', 'integer', 'min:1', 'max:12'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'diets' => ['array', 'max:'.count(MealDietEnum::cases())],
            'diets.*' => ['string', 'distinct', Rule::enum(MealDietEnum::class)],
            'avoid' => ['nullable', 'string', 'max:255', $this->ingredientList(...)],
            'goals' => ['array', 'max:'.count(MealGoalEnum::cases())],
            'goals.*' => ['string', 'distinct', Rule::enum(MealGoalEnum::class)],
            'confidence' => ['required', Rule::enum(CookingConfidenceEnum::class)],
            'equipment' => ['array', 'max:'.count(KitchenEquipmentEnum::cases())],
            'equipment.*' => ['string', 'distinct', Rule::enum(KitchenEquipmentEnum::class)],
            'use_up' => ['nullable', 'string', 'max:255', $this->ingredientList(...)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** A comma list of ingredients, each a word or two, not a paragraph. */
    private function ingredientList(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $items = array_filter(array_map(trim(...), explode(',', $value)));

        if (count($items) > self::LIST_ITEMS) {
            $fail('List up to '.self::LIST_ITEMS.' ingredients, separated by commas.');
        } elseif (collect($items)->contains(fn (string $item) => mb_strlen($item) > self::LIST_ITEM_LENGTH)) {
            $fail('Keep each ingredient to a few words, separated by commas.');
        }
    }

    /** Plain lines: the AI gets them as data, never as part of its instructions. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'avoid' => $this->plainText('avoid'),
            'use_up' => $this->plainText('use_up'),
            'notes' => $this->plainText('notes'),
        ]);
    }

    public function toInput(): AiMealPlanAnswersInput
    {
        return AiMealPlanAnswersInput::from($this->validated());
    }
}
