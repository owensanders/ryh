<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Enums\PaletteEnum;
use App\Models\ShoppingList;

class RenameShoppingListAction
{
    /** @param  array<string, mixed>  $data  ShoppingListRequest's validated input: name, colour. */
    public function handle(ShoppingList $list, array $data): ShoppingList
    {
        $list->update(['name' => $data['name'], 'colour' => PaletteEnum::from($data['colour'])]);

        return $list->refresh();
    }
}
