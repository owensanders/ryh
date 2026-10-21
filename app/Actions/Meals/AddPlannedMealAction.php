<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Data\Input\PlannedMealInputData;
use App\Data\PlannedMealData;
use App\Models\Household;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Repositories\MealPlanRepository;

class AddPlannedMealAction
{
    public function __construct(
        private readonly MealPlanRepository $meals,
        private readonly AddRecipeIngredientsToListAction $addIngredients,
    ) {}

    public function handle(Household $household, PlannedMealInputData $input, User $actor): PlannedMealData
    {
        $meal = $this->meals->create($household, $input);

        if ($input->addIngredientsToListId !== null) {
            $this->addIngredients->handle($household, $meal->recipe, $input->addIngredientsToListId);
        }

        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealAssignedNotification($meal, $actor));
        }

        return PlannedMealData::fromModel($meal);
    }
}
