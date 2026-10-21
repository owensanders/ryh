<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Data\Input\PlannedMealInputData;
use App\Data\PlannedMealData;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealEditedNotification;
use App\Repositories\MealPlanRepository;
use LogicException;

class UpdatePlannedMealAction
{
    public function __construct(
        private readonly MealPlanRepository $meals,
        private readonly AddRecipeIngredientsToListAction $addIngredients,
    ) {}

    public function handle(PlannedMeal $meal, PlannedMealInputData $changes, User $actor): PlannedMealData
    {
        $previousCookId = $meal->cook_id;

        $updated = $this->meals->update($meal, $changes);

        $household = $updated->household ?? throw new LogicException('A planned meal always belongs to a household.');

        if ($changes->addIngredientsToListId !== null) {
            $this->addIngredients->handle($household, $updated->recipe, $changes->addIngredientsToListId);
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
