<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Inputs\ShoppingItemInput;
use App\Models\Household;
use App\Models\ShoppingList;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddShoppingItemAction
{
    /** @return ShoppingList The list it went on. */
    public function handle(Household $household, string $listSlug, ShoppingItemInput $input): ShoppingList
    {
        $list = $household->shoppingLists()->where('slug', $listSlug)->first();

        if ($list === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class);
        }

        $list->items()->create([
            'name' => $input->name,
            'quantity' => $input->quantity ?? 'x1',
            'position' => (int) $list->items()->max('position') + 1,
        ]);

        return $list;
    }
}
