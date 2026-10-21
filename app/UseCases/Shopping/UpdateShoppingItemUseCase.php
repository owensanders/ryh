<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Data\ShoppingItemData;
use App\Models\ShoppingItem;
use App\Repositories\ShoppingRepository;

class UpdateShoppingItemUseCase
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function execute(ShoppingItem $item, string $name, ?string $quantity): ShoppingItemData
    {
        return ShoppingItemData::fromModel($this->shopping->updateItem($item, trim($name), $quantity));
    }
}
