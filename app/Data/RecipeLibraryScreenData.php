<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * The recipe library screen. Props that query are closures, so a partial
 * reload (sending ingredients to a list) runs only the ones it asks for.
 */
class RecipeLibraryScreenData extends Data
{
    /**
     * @param  Lazy|array<int, RecipeData>  $recipes
     * @param  list<array{value: string, label: string}>  $tagOptions
     * @param  Lazy|array<int, ShoppingListData>  $shoppingLists  Where a recipe's ingredients can be sent.
     */
    public function __construct(
        public Lazy|array $recipes,
        public array $tagOptions,
        public Lazy|array $shoppingLists,
    ) {}
}
