<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Enums\MealSlotEnum;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealAssignedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AddPlannedMealAction
{
    public function __construct(
        private readonly ResolvePlannedRecipeAction $resolveRecipe,
        private readonly AddRecipeIngredientsToListAction $addIngredients,
    ) {}

    /**
     * @param  array<string, mixed>  $data  PlannedMealRequest's validated input: recipe_id or
     *                                      new_recipe_name (with any new_recipe_* details),
     *                                      new_recipe_shopping_list_id, planned_on, slot, cook_id.
     */
    public function handle(Household $household, array $data, User $actor): PlannedMeal
    {
        // A recipe named on the form and its meal are made together, or not at all.
        $meal = DB::transaction(fn (): PlannedMeal => $household->plannedMeals()->create([
            'recipe_id' => $this->resolveRecipe->handle($household, $data)->id,
            'planned_on' => CarbonImmutable::parse($data['planned_on']),
            'slot' => MealSlotEnum::from($data['slot']),
            'cook_id' => $data['cook_id'],
        ]));

        $meal->refresh()->load(['recipe', 'cook']);

        if ($data['new_recipe_shopping_list_id'] !== null) {
            $this->addIngredients->handle($household, $meal->recipe, $data['new_recipe_shopping_list_id']);
        }

        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealAssignedNotification($meal, $actor));
        }

        return $meal;
    }
}
