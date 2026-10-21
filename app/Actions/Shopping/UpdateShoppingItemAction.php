<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Data\ShoppingItemData;
use App\Models\ShoppingItem;
use App\Repositories\ShoppingRepository;

class UpdateShoppingItemAction
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    public function handle(ShoppingItem $item, string $name, ?string $quantity): ShoppingItemData
    {
        return ShoppingItemData::fromModel($this->shopping->updateItem($item, trim($name), $quantity));
    }
}
