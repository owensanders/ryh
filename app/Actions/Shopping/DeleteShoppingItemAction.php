<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\ShoppingItem;

class DeleteShoppingItemAction
{
    public function handle(ShoppingItem $item): void
    {
        $item->delete();
    }
}
