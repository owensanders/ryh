<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The recipe library screen. */
class RecipeLibraryScreenData extends Data
{
    /**
     * @param  array<int, RecipeData>  $recipes
     * @param  list<array{value: string, label: string}>  $tagOptions
     * @param  array<int, ShoppingListData>  $shoppingLists  Where a recipe's ingredients can be sent.
     */
    public function __construct(
        public array $recipes,
        public array $tagOptions,
        public array $shoppingLists,
    ) {}
}
