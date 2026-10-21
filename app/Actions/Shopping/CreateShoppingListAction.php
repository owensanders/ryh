<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Data\Input\ShoppingListInputData;
use App\Models\Household;
use App\Models\ShoppingList;
use Illuminate\Support\Str;

class CreateShoppingListAction
{
    public function handle(Household $household, ShoppingListInputData $input): ShoppingList
    {
        return $household->shoppingLists()->create([
            'name' => $input->name,
            'slug' => $this->uniqueSlug($household, $input->name),
            'colour' => $input->colour,
        ]);
    }

    /** Slugs are unique per household, so a name collision gets a numeric suffix. */
    private function uniqueSlug(Household $household, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while ($household->shoppingLists()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
