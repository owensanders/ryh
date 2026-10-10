<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CookingConfidenceEnum;
use App\Enums\KitchenEquipmentEnum;
use App\Enums\MealDietEnum;
use App\Enums\MealGoalEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAiMealPlanRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'people' => ['required', 'integer', 'min:1', 'max:12'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'diets' => ['array', 'max:'.count(MealDietEnum::cases())],
            'diets.*' => ['string', 'distinct', Rule::enum(MealDietEnum::class)],
            'avoid' => ['nullable', 'string', 'max:255'],
            'goals' => ['array', 'max:'.count(MealGoalEnum::cases())],
            'goals.*' => ['string', 'distinct', Rule::enum(MealGoalEnum::class)],
            'confidence' => ['required', Rule::enum(CookingConfidenceEnum::class)],
            'equipment' => ['array', 'max:'.count(KitchenEquipmentEnum::cases())],
            'equipment.*' => ['string', 'distinct', Rule::enum(KitchenEquipmentEnum::class)],
            'use_up' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
