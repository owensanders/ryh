<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Inputs\PlannedMealInput;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use App\Notifications\MealEditedNotification;
use Illuminate\Support\Facades\DB;
use LogicException;

class UpdatePlannedMealAction
{
    public function __construct(
        private readonly ResolvePlannedRecipeAction $resolveRecipe,
        private readonly AddRecipeIngredientsToListAction $addIngredients,
    ) {}

    public function handle(Household $household, PlannedMeal $meal, PlannedMealInput $input, User $actor): PlannedMeal
    {
        $previousCookId = $meal->cook_id;
        $household = $meal->household ?? throw new LogicException('A planned meal always belongs to a household.');

        DB::transaction(fn () => $meal->update([
            'recipe_id' => $this->resolveRecipe->handle($household, $input)->id,
            'planned_on' => $input->plannedOn,
            'slot' => $input->slot,
            'cook_id' => $input->cookId,
        ]));

        $meal->refresh()->load(['recipe', 'cook']);

        if ($input->newRecipeShoppingListId !== null) {
            $this->addIngredients->handle($household, $meal->recipe, $input->newRecipeShoppingListId);
        }

        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $notification = $meal->cook_id !== $previousCookId
                ? new MealAssignedNotification($household, $meal, $actor)
                : new MealEditedNotification($household, $meal, $actor);

            $meal->cook?->notify($notification);
        }

        return $meal;
    }
}
