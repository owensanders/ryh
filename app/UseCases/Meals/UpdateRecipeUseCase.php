<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Data\Input\RecipeInputData;
use App\Data\RecipeData;
use App\Models\Recipe;
use App\Repositories\MealPlanRepository;

class UpdateRecipeUseCase
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function execute(Recipe $recipe, RecipeInputData $changes): RecipeData
    {
        return RecipeData::fromModel($this->meals->updateRecipe($recipe, $changes));
    }
}
