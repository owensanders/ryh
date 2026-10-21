<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Models\ShoppingList;
use App\Repositories\ShoppingRepository;

class DeleteShoppingListUseCase
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function execute(ShoppingList $list): void
    {
        $this->shopping->deleteList($list);
    }
}
