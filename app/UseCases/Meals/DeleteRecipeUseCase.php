<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Models\Recipe;
use App\Repositories\MealPlanRepository;

class DeleteRecipeUseCase
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function execute(Recipe $recipe): void
    {
        $this->meals->deleteRecipe($recipe);
    }
}
