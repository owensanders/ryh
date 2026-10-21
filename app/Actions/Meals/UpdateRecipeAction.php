<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Data\Input\RecipeInputData;
use App\Data\RecipeData;
use App\Models\Recipe;
use App\Repositories\MealPlanRepository;

class UpdateRecipeAction
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function handle(Recipe $recipe, RecipeInputData $changes): RecipeData
    {
        return RecipeData::fromModel($this->meals->updateRecipe($recipe, $changes));
    }
}
