<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Data\Input\RecipeInputData;
use App\Data\RecipeData;
use App\Models\Household;
use App\Repositories\MealPlanRepository;

class AddRecipeAction
{
    public function __construct(
        private readonly MealPlanRepository $meals,
        private readonly AddRecipeIngredientsToListAction $addIngredients,
    ) {}

    /** With a list picked, the new recipe's ingredients go straight onto it. */
    public function handle(Household $household, RecipeInputData $input, ?int $shoppingListId = null): RecipeData
    {
        $recipe = $this->meals->createRecipe($household, $input);

        if ($shoppingListId !== null) {
            $this->addIngredients->handle($household, $recipe, $shoppingListId);
        }

        return RecipeData::fromModel($recipe);
    }
}
