<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\Household;
use App\Models\ShoppingList;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddShoppingItemAction
{
    /** @return ShoppingList The list it went on. */
    public function handle(Household $household, string $listSlug, string $name, ?string $quantity = null): ShoppingList
    {
        $list = $household->shoppingLists()->where('slug', $listSlug)->first();

        if ($list === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class);
        }

        $list->items()->create([
            'name' => trim($name),
            'quantity' => $quantity ?? 'x1',
            'position' => $list->items()->nextPosition(),
        ]);

        return $list;
    }
}
