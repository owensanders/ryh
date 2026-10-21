<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\ShoppingItem;
use App\Repositories\ShoppingRepository;

class DeleteShoppingItemAction
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function handle(ShoppingItem $item): void
    {
        $this->shopping->deleteItem($item);
    }
}
