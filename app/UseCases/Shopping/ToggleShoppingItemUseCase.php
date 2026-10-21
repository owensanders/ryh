<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Data\ShoppingItemData;
use App\Models\ShoppingItem;
use App\Repositories\ShoppingRepository;

class ToggleShoppingItemUseCase
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function execute(ShoppingItem $item): ShoppingItemData
    {
        return ShoppingItemData::fromModel(
            $this->shopping->setItemCompletion($item, ! $item->isDone()),
        );
    }
}
