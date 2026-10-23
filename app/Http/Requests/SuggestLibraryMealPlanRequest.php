<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MealDietEnum;
use App\Inputs\LibraryMealPlanInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuggestLibraryMealPlanRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'diets' => ['array', 'max:'.count(MealDietEnum::cases())],
            'diets.*' => ['string', 'distinct', Rule::enum(MealDietEnum::class)],
            'avoid' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toInput(): LibraryMealPlanInput
    {
        return LibraryMealPlanInput::from($this->validated());
    }
}
