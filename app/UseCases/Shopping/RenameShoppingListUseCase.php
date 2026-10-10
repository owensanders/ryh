<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\Input\ShoppingListInputData;
use App\Data\ShoppingListData;
use App\Models\ShoppingList;

class RenameShoppingListUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function execute(ShoppingList $list, ShoppingListInputData $changes): ShoppingListData
    {
        return ShoppingListData::fromModel($this->shopping->updateList($list, $changes->name, $changes->colour), withItems: false);
    }
}
