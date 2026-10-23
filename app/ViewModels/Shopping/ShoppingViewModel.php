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
        // Only the open list's items are loaded; the sidebar shows counts.
        $lists = $household->shoppingLists()->withItemCounts()->get();
        $active = $slug !== null ? $lists->firstWhere('slug', $slug) : $lists->first();

        if ($slug !== null && $active === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class);
        }

        return new ShoppingScreenData(
            lists: $lists->map(fn (ShoppingList $list) => ShoppingListData::summary($list, $viewer))->values()->all(),
            active: $active !== null ? ShoppingListData::fromModel($active->load('items'), $viewer) : null,
        );
    }
}
