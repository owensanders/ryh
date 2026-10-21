<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\ShoppingList;
use Illuminate\Support\Str;

class CreateShoppingListAction
{
    /** @param  array<string, mixed>  $data  ShoppingListRequest's validated input: name, colour. */
    public function handle(Household $household, array $data): ShoppingList
    {
        return $household->shoppingLists()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($household, $data['name']),
            'colour' => PaletteEnum::from($data['colour']),
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
