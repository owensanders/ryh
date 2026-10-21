<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Data\Input\ShoppingListInputData;
use App\Models\ShoppingList;

class RenameShoppingListAction
{
    public function handle(ShoppingList $list, ShoppingListInputData $changes): ShoppingList
    {
        $list->update(['name' => $changes->name, 'colour' => $changes->colour]);

        return $list->refresh();
    }
}
