<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\PlannedMealData;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealEditedNotification;
use App\UseCases\Meals\Concerns\PushesNewRecipeIngredients;
use App\UseCases\Shopping\AddRecipeIngredientsToListUseCase;
use LogicException;

class UpdatePlannedMealUseCase
{
    use PushesNewRecipeIngredients;

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
        private readonly ShoppingRepositoryInterface $shopping,
        private readonly AddRecipeIngredientsToListUseCase $addIngredients,
    ) {}

    /** @param  array<string, mixed>  $attributes */
    public function execute(PlannedMeal $meal, array $attributes, User $actor): PlannedMealData
    {
        $previousCookId = $meal->cook_id;

        $updated = $this->meals->update($meal, $attributes);

        $household = $updated->household ?? throw new LogicException('A planned meal always belongs to a household.');
        $this->pushNewRecipeIngredients($household, $updated, $attributes);

        if ($updated->cook_id !== null && $updated->cook_id !== $actor->id) {
            $notification = $updated->cook_id !== $previousCookId
                ? new MealAssignedNotification($updated, $actor)
                : new MealEditedNotification($updated, $actor);

            $updated->cook?->notify($notification);
        }

        return PlannedMealData::fromModel($updated);
    }
}
