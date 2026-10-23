<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Inputs\ShoppingItemInput;
use App\Models\ShoppingItem;

class UpdateShoppingItemAction
{
    public function handle(ShoppingItem $item, ShoppingItemInput $input): ShoppingItem
    {
        $item->update(['name' => $input->name, 'quantity' => $input->quantity]);

        return $item->refresh();
    }
}
