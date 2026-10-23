<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Inputs\PlannedMealInput;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use Illuminate\Support\Facades\DB;

class AddPlannedMealAction
{
    public function __construct(
        private readonly ResolvePlannedRecipeAction $resolveRecipe,
        private readonly AddRecipeIngredientsToListAction $addIngredients,
    ) {}

    public function handle(Household $household, PlannedMealInput $input, User $actor): PlannedMeal
    {
        // A recipe named on the form and its meal are made together, or not at all.
        $meal = DB::transaction(fn (): PlannedMeal => $household->plannedMeals()->create([
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
            $meal->cook?->notify(new MealAssignedNotification($household, $meal, $actor));
        }

        return $meal;
    }
}
