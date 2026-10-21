<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Models\ShoppingItem;
use App\Repositories\ShoppingRepository;

class DeleteShoppingItemUseCase
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function execute(ShoppingItem $item): void
    {
        $this->shopping->deleteItem($item);
    }
}
