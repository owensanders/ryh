<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Data\Input\RecipeInputData;
use App\Data\RecipeData;
use App\Models\Household;
use App\Repositories\MealPlanRepository;
use App\UseCases\Shopping\AddRecipeIngredientsToListUseCase;

class AddRecipeUseCase
{
    public function __construct(
        private readonly MealPlanRepository $meals,
        private readonly AddRecipeIngredientsToListUseCase $addIngredients,
    ) {}

    /** With a list picked, the new recipe's ingredients go straight onto it. */
    public function execute(Household $household, RecipeInputData $input, ?int $shoppingListId = null): RecipeData
    {
        $recipe = $this->meals->createRecipe($household, $input);

        if ($shoppingListId !== null) {
            $this->addIngredients->execute($household, $recipe, $shoppingListId);
        }

        return RecipeData::fromModel($recipe);
    }
}
