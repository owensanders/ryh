<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Enums\Palette;
use App\Models\Household;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ShoppingRepository implements ShoppingRepositoryInterface
{
    /** @return Collection<int, ShoppingList> */
    public function listsFor(Household $household): Collection
    {
        return $household->shoppingLists()->with('items')->get();
    }

    public function findListBySlug(Household $household, string $slug): ?ShoppingList
    {
        return $household->shoppingLists()->with('items')->where('slug', $slug)->first();
    }

    /** Prefers a list that actually has items, so the dashboard doesn't spotlight an empty one. */
    public function defaultListFor(Household $household): ?ShoppingList
    {
        $lists = $household->shoppingLists()->with('items')->get();

        return $lists->first(fn (ShoppingList $list) => $list->items->isNotEmpty());
    }

    public function createList(Household $household, string $name, Palette $colour): ShoppingList
    {
        return $household->shoppingLists()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($household, $name),
            'colour' => $colour,
        ]);
    }

    public function updateList(ShoppingList $list, string $name, Palette $colour): ShoppingList
    {
        $list->update(['name' => $name, 'colour' => $colour]);

        return $list->refresh();
    }

    public function deleteList(ShoppingList $list): void
    {
        $list->delete();
    }

    public function addItem(ShoppingList $list, string $name, ?string $quantity): ShoppingItem
    {
        return $list->items()->create([
            'name' => $name,
            'quantity' => $quantity,
            'position' => (int) $list->items()->max('position') + 1,
        ]);
    }

    public function updateItem(ShoppingItem $item, string $name, ?string $quantity): ShoppingItem
    {
        $item->update(['name' => $name, 'quantity' => $quantity]);

        return $item->refresh();
    }

    public function deleteItem(ShoppingItem $item): void
    {
        $item->delete();
    }

    public function setItemCompletion(ShoppingItem $item, bool $done): ShoppingItem
    {
        $item->update(['completed_at' => $done ? now() : null]);

        return $item->refresh();
    }

    /** Slugs are unique per household, so a name collision gets a numeric suffix. */
    private function uniqueSlug(Household $household, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while ($this->findListBySlug($household, $slug) !== null) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
