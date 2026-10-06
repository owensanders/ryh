<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CookingConfidence;
use App\Enums\KitchenEquipment;
use App\Enums\MealDiet;
use App\Enums\MealGoal;
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
            'diets' => ['array', 'max:'.count(MealDiet::cases())],
            'diets.*' => ['string', 'distinct', Rule::enum(MealDiet::class)],
            'avoid' => ['nullable', 'string', 'max:255'],
            'goals' => ['array', 'max:'.count(MealGoal::cases())],
            'goals.*' => ['string', 'distinct', Rule::enum(MealGoal::class)],
            'confidence' => ['required', Rule::enum(CookingConfidence::class)],
            'equipment' => ['array', 'max:'.count(KitchenEquipment::cases())],
            'equipment.*' => ['string', 'distinct', Rule::enum(KitchenEquipment::class)],
            'use_up' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
