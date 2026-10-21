<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\ShoppingItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reached through a list, so already scoped to it: `$list->items()->nextPosition()`.
 *
 * @extends Builder<ShoppingItem>
 */
class ShoppingItemBuilder extends Builder
{
    /** Where a new item goes: after everything already on the list. */
    public function nextPosition(): int
    {
        return (int) $this->clone()->max('position') + 1;
    }
}
