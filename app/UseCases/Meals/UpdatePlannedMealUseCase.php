<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Data\Input\PlannedMealInputData;
use App\Data\PlannedMealData;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealEditedNotification;
use App\UseCases\Shopping\AddRecipeIngredientsToListUseCase;
use LogicException;

class UpdatePlannedMealUseCase
{
    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
        private readonly AddRecipeIngredientsToListUseCase $addIngredients,
    ) {}

    public function execute(PlannedMeal $meal, PlannedMealInputData $changes, User $actor): PlannedMealData
    {
        $previousCookId = $meal->cook_id;

        $updated = $this->meals->update($meal, $changes);

        $household = $updated->household ?? throw new LogicException('A planned meal always belongs to a household.');

        if ($changes->addIngredientsToListId !== null) {
            $this->addIngredients->execute($household, $updated->recipe, $changes->addIngredientsToListId);
        }

        if ($updated->cook_id !== null && $updated->cook_id !== $actor->id) {
            $notification = $updated->cook_id !== $previousCookId
                ? new MealAssignedNotification($updated, $actor)
                : new MealEditedNotification($updated, $actor);

            $updated->cook?->notify($notification);
        }

        return PlannedMealData::fromModel($updated);
    }
}
