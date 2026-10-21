<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Data\RecipeData;
use App\Models\Household;
use App\Repositories\MealPlanRepository;

class GetRecipeLibraryUseCase
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    /** @return array<int, RecipeData> */
    public function execute(Household $household): array
    {
        return $this->meals->allRecipes($household)
            ->map(RecipeData::fromModel(...))
            ->values()
            ->all();
    }
}
