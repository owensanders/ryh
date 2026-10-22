<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ShoppingItem;
use App\Models\User;
use Spatie\LaravelData\Data;

class ShoppingItemData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $quantity,
        public bool $done,
        public AbilitiesData $can,
    ) {}

    public static function fromModel(ShoppingItem $item, User $viewer): self
    {
        return new self(
            id: $item->id,
            name: $item->name,
            quantity: $item->quantity,
            done: $item->isDone(),
            can: AbilitiesData::for($viewer, $item, 'update', 'delete', 'toggle'),
        );
    }
}
