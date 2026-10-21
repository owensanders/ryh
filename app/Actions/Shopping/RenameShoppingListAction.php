<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Data\Input\ShoppingListInputData;
use App\Data\ShoppingListData;
use App\Models\ShoppingList;
use App\Repositories\ShoppingRepository;

class RenameShoppingListAction
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function handle(ShoppingList $list, ShoppingListInputData $changes): ShoppingListData
    {
        return ShoppingListData::fromModel($this->shopping->updateList($list, $changes->name, $changes->colour), withItems: false);
    }
}
