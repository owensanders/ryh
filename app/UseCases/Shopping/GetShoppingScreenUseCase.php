<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\ShoppingListData;
use App\Data\ShoppingScreenData;
use App\Models\Household;
use App\Models\ShoppingList;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetShoppingScreenUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function execute(Household $household, ?string $slug = null): ShoppingScreenData
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
