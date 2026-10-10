<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MealSlotEnum;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlannedMeal> */
class PlannedMealFactory extends Factory
{
    protected $model = PlannedMeal::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'recipe_id' => Recipe::factory(),
            'cook_id' => User::factory(),
            'planned_on' => now()->toDateString(),
            'slot' => MealSlotEnum::Dinner,
            'missing_ingredients' => 0,
        ];
    }
}
