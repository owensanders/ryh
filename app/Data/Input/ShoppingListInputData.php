<?php

declare(strict_types=1);

namespace App\Data\Input;

use App\Enums\PaletteEnum;

/** A shopping list as the add/rename form submits it, validated by ShoppingListRequest. */
final readonly class ShoppingListInputData
{
    public function __construct(
        public string $name,
        public PaletteEnum $colour,
    ) {}
}
