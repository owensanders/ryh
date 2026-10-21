<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\ShoppingList;
use App\Repositories\ShoppingRepository;

class DeleteShoppingListAction
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function handle(ShoppingList $list): void
    {
        $this->shopping->deleteList($list);
    }
}
