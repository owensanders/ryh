<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\Recipe;
use App\Repositories\MealPlanRepository;

class DeleteRecipeAction
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function handle(Recipe $recipe): void
    {
        $this->meals->deleteRecipe($recipe);
    }
}
