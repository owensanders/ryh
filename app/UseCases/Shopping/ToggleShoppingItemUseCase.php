<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\ShoppingItemData;
use App\Models\ShoppingItem;

class ToggleShoppingItemUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function execute(ShoppingItem $item): ShoppingItemData
    {
        return ShoppingItemData::fromModel(
            $this->shopping->setItemCompletion($item, ! $item->isDone()),
        );
    }
}
