<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Inputs\ShoppingListInput;
use App\Models\ShoppingList;

class RenameShoppingListAction
{
    public function handle(ShoppingList $list, ShoppingListInput $input): ShoppingList
    {
        $list->update(['name' => $input->name, 'colour' => $input->colour]);

        return $list->refresh();
    }
}
