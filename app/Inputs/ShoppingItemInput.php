<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Dto;

/** A shopping item as ShoppingItemRequest validated it. */
final class ShoppingItemInput extends Dto
{
    public function __construct(
        public readonly string $name,
        /** Left empty, a new item is "x1". */
        public readonly ?string $quantity = null,
    ) {}
}
