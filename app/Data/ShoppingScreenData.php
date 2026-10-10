<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The shopping screen, opened on one list. */
class ShoppingScreenData extends Data
{
    /**
     * @param  array<int, ShoppingListData>  $lists
     */
    public function __construct(
        public array $lists,
        public ?ShoppingListData $active,
    ) {}
}
