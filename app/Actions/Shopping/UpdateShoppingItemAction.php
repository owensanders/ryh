<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\ShoppingItem;

class UpdateShoppingItemAction
{
    public function handle(ShoppingItem $item, string $name, ?string $quantity): ShoppingItem
    {
        $item->update(['name' => trim($name), 'quantity' => $quantity]);

        return $item->refresh();
    }
}
