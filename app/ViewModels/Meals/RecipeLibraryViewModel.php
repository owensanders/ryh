<?php

declare(strict_types=1);

namespace App\ViewModels\Meals;

use App\Data\RecipeData;
use App\Data\RecipeLibraryScreenData;
use App\Data\ShoppingListData;
use App\Enums\RecipeTagEnum;
use App\Models\Household;
use App\Models\ShoppingList;

class RecipeLibraryViewModel
{
    public function toData(Household $household): RecipeLibraryScreenData
    {
        return new RecipeLibraryScreenData(
            recipes: $household->recipes()->inLibraryOrder()->get()
                ->map(RecipeData::fromModel(...))
                ->values()
                ->all(),
            tagOptions: RecipeTagEnum::options(),
            shoppingLists: $household->shoppingLists()->with('items')->get()
                ->map(fn (ShoppingList $list) => ShoppingListData::fromModel($list, withItems: false))
                ->values()
                ->all(),
        );
    }
}
