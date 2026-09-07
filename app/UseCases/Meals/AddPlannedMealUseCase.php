<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\PlannedMealData;
use App\Models\Household;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\UseCases\Meals\Concerns\PushesNewRecipeIngredients;
use App\UseCases\Shopping\AddRecipeIngredientsToListUseCase;

class AddPlannedMealUseCase
{
    use PushesNewRecipeIngredients;

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
        private readonly ShoppingRepositoryInterface $shopping,
        private readonly AddRecipeIngredientsToListUseCase $addIngredients,
    ) {}

    /** @param  array<string, mixed>  $attributes */
    public function execute(Household $household, array $attributes, User $actor): PlannedMealData
    {
        $meal = $this->meals->create($household, $attributes);

        $this->pushNewRecipeIngredients($household, $meal, $attributes);

        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealAssignedNotification($meal, $actor));
        }

        return PlannedMealData::fromModel($meal);
    }
}
