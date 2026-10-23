<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Enums\PaletteEnum;
use Spatie\LaravelData\Dto;

/** A shopping list as ShoppingListRequest validated it. */
final class ShoppingListInput extends Dto
{
    public function __construct(
        public readonly string $name,
        public readonly PaletteEnum $colour,
    ) {}
}
