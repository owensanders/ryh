<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\ShoppingItem;

class ToggleShoppingItemAction
{
    public function handle(ShoppingItem $item): ShoppingItem
    {
        $item->update(['completed_at' => $item->isDone() ? null : now()]);

        return $item->refresh();
    }
}
