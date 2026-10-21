<?php

declare(strict_types=1);

namespace App\ViewModels\Shopping;

use App\Data\ShoppingListData;
use App\Data\ShoppingScreenData;
use App\Models\Household;
use App\Models\ShoppingList;
use App\Repositories\ShoppingRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShoppingViewModel
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function toData(Household $household, ?string $slug = null): ShoppingScreenData
    {
        $lists = $this->shopping->listsFor($household);
        $active = $slug !== null ? $lists->firstWhere('slug', $slug) : $lists->first();

        if ($slug !== null && $active === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class);
        }

        return new ShoppingScreenData(
            lists: $lists->map(fn (ShoppingList $list) => ShoppingListData::fromModel($list, withItems: false))->values()->all(),
            active: $active !== null ? ShoppingListData::fromModel($active) : null,
        );
    }
}
