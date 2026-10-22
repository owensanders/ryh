<?php

declare(strict_types=1);

namespace App\ViewModels\Shopping;

use App\Data\ShoppingListData;
use App\Data\ShoppingScreenData;
use App\Models\Household;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShoppingViewModel
{
    public function toData(Household $household, User $viewer, ?string $slug = null): ShoppingScreenData
    {
        $lists = $household->shoppingLists()->with('items')->get();
        $active = $slug !== null ? $lists->firstWhere('slug', $slug) : $lists->first();

        if ($slug !== null && $active === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class);
        }

        return new ShoppingScreenData(
            lists: $lists->map(fn (ShoppingList $list) => ShoppingListData::fromModel($list, $viewer, withItems: false))->values()->all(),
            active: $active !== null ? ShoppingListData::fromModel($active, $viewer) : null,
        );
    }
}
