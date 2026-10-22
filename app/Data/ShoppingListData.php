<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use Spatie\LaravelData\Data;

class ShoppingListData extends Data
{
    /** @param array<int, ShoppingItemData> $items */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $colour,
        public string $colourKey,
        public int $remaining,
        public int $total,
        public array $items,
        public AbilitiesData $can,
    ) {}

    /** @param  bool  $withItems  lists in the sidebar only need their counts. */
    public static function fromModel(ShoppingList $list, User $viewer, bool $withItems = true): self
    {
        $items = $list->items;

        return new self(
            id: $list->id,
            name: $list->name,
            slug: $list->slug,
            colour: $list->colour->cssVar(),
            colourKey: $list->colour->value,
            remaining: $items->whereNull('completed_at')->count(),
            total: $items->count(),
            items: $withItems ? $items->map(fn (ShoppingItem $item) => ShoppingItemData::fromModel($item, $viewer))->values()->all() : [],
            can: AbilitiesData::for($viewer, $list, 'update', 'delete'),
        );
    }
}
