<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MealDiet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuggestLibraryMealPlanRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'diets' => ['array', 'max:'.count(MealDiet::cases())],
            'diets.*' => ['string', 'distinct', Rule::enum(MealDiet::class)],
            'avoid' => ['nullable', 'string', 'max:255'],
        ];
    }
}
