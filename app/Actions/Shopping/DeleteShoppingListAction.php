<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\ShoppingList;

class DeleteShoppingListAction
{
    public function handle(ShoppingList $list): void
    {
        $list->delete();
    }
}
