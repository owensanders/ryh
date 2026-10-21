<?php

declare(strict_types=1);

namespace App\ViewModels\Meals;

use App\Data\RecipeData;
use App\Data\RecipeLibraryScreenData;
use App\Data\ShoppingListData;
use App\Enums\RecipeTagEnum;
use App\Models\Household;
use App\Models\ShoppingList;
use App\Repositories\MealPlanRepository;
use App\Repositories\ShoppingRepository;

class RecipeLibraryViewModel
{
    public function __construct(
        private readonly MealPlanRepository $meals,
        private readonly ShoppingRepository $shopping,
    ) {}

    public function toData(Household $household): RecipeLibraryScreenData
    {
        return new RecipeLibraryScreenData(
            recipes: $this->meals->allRecipes($household)
                ->map(RecipeData::fromModel(...))
                ->values()
                ->all(),
            tagOptions: RecipeTagEnum::options(),
            shoppingLists: $this->shopping->listsFor($household)
                ->map(fn (ShoppingList $list) => ShoppingListData::fromModel($list, withItems: false))
                ->values()
                ->all(),
        );
    }
}
