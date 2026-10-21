<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Data\ShoppingListData;
use App\Models\Household;
use App\Models\ShoppingList;
use App\Repositories\ShoppingRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddShoppingItemUseCase
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    /** @return ShoppingListData The list it went on. */
    public function execute(Household $household, string $listSlug, string $name, ?string $quantity = null): ShoppingListData
    {
        $list = $this->shopping->findListBySlug($household, $listSlug);

        if ($list === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class);
        }

        $this->shopping->addItem($list, trim($name), $quantity ?? 'x1');

        return ShoppingListData::fromModel($list, withItems: false);
    }
}
