<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;
use LogicException;
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

    /** A list with its items, which must be loaded. */
    public static function fromModel(ShoppingList $list, User $viewer): self
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
            items: $items->map(fn (ShoppingItem $item) => ShoppingItemData::fromModel($item, $viewer))->values()->all(),
            can: AbilitiesData::for($viewer, $list, 'update', 'delete'),
        );
    }

    /** A list's name and counts, for a sidebar or picker: loaded with ShoppingList::withItemCounts(), not its items. */
    public static function summary(ShoppingList $list, User $viewer): self
    {
        return new self(
            id: $list->id,
            name: $list->name,
            slug: $list->slug,
            colour: $list->colour->cssVar(),
            colourKey: $list->colour->value,
            remaining: $list->remaining_items_count ?? throw new LogicException('Load the list withItemCounts() to summarise it.'),
            total: $list->items_count ?? throw new LogicException('Load the list withItemCounts() to summarise it.'),
            items: [],
            can: AbilitiesData::for($viewer, $list, 'update', 'delete'),
        );
    }
}
