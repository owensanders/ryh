<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Data\Input\PlannedMealInputData;
use App\Data\PlannedMealData;
use App\Models\Household;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Repositories\MealPlanRepository;
use App\UseCases\Shopping\AddRecipeIngredientsToListUseCase;

class AddPlannedMealUseCase
{
    public function __construct(
        private readonly MealPlanRepository $meals,
        private readonly AddRecipeIngredientsToListUseCase $addIngredients,
    ) {}

    public function execute(Household $household, PlannedMealInputData $input, User $actor): PlannedMealData
    {
        $meal = $this->meals->create($household, $input);

        if ($input->addIngredientsToListId !== null) {
            $this->addIngredients->execute($household, $meal->recipe, $input->addIngredientsToListId);
        }

        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealAssignedNotification($meal, $actor));
        }

        return PlannedMealData::fromModel($meal);
    }
}
