<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\ShoppingListData;
use App\Models\Household;

class AddShoppingItemUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    /** @return ShoppingListData The list it went on. */
    public function execute(Household $household, string $listSlug, string $name, ?string $quantity = null): ShoppingListData
    {
        $list = $this->shopping->findListBySlug($household, $listSlug);

        abort_if($list === null, 404);

        $this->shopping->addItem($list, trim($name), $quantity ?? 'x1');

        return ShoppingListData::fromModel($list, withItems: false);
    }
}
