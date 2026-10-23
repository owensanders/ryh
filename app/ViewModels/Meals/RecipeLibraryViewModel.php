<?php

declare(strict_types=1);

namespace App\ViewModels\Meals;

use App\Data\RecipeData;
use App\Data\RecipeLibraryScreenData;
use App\Data\ShoppingListData;
use App\Enums\RecipeTagEnum;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use Spatie\LaravelData\Lazy;

class RecipeLibraryViewModel
{
    public function toData(Household $household, User $viewer): RecipeLibraryScreenData
    {
        return new RecipeLibraryScreenData(
            recipes: Lazy::closure(fn () => $household->recipes()->inLibraryOrder()->get()
                ->map(fn (Recipe $recipe) => RecipeData::fromModel($recipe, $viewer))
                ->values()
                ->all()),
            tagOptions: RecipeTagEnum::options(),
            shoppingLists: Lazy::closure(fn () => $household->shoppingLists()->withItemCounts()->get()
                ->map(fn (ShoppingList $list) => ShoppingListData::summary($list, $viewer))
                ->values()
                ->all()),
        );
    }
}
