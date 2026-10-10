<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\ShoppingListData;
use App\Models\Household;
use App\Models\ShoppingList;

class GetShoppingScreenUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    /** @return array{lists: array<int, ShoppingListData>, active: ?ShoppingListData} */
    public function execute(Household $household, ?ShoppingList $active): array
    {
        $lists = $this->shopping->listsFor($household);
        $active ??= $lists->first();

        return [
            'lists' => $lists->map(fn (ShoppingList $list) => ShoppingListData::fromModel($list, withItems: false))->values()->all(),
            'active' => $active !== null ? ShoppingListData::fromModel($active) : null,
        ];
    }
}
