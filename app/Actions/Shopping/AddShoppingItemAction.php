<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Data\ShoppingListData;
use App\Models\Household;
use App\Models\ShoppingList;
use App\Repositories\ShoppingRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddShoppingItemAction
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    /** @return ShoppingListData The list it went on. */
    public function handle(Household $household, string $listSlug, string $name, ?string $quantity = null): ShoppingListData
    {
        $list = $this->shopping->findListBySlug($household, $listSlug);

        if ($list === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class);
        }

        $this->shopping->addItem($list, trim($name), $quantity ?? 'x1');

        return ShoppingListData::fromModel($list, withItems: false);
    }
}
