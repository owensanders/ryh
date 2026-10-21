<?php

declare(strict_types=1);

namespace App\Actions\Shopping;

use App\Models\Household;
use App\Models\Recipe;
use App\Models\ShoppingList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AddRecipeIngredientsToListAction
{
    /**
     * @return ShoppingList The list they went on.
     *
     * @throws ModelNotFoundException When the list isn't one of the household's.
     */
    public function handle(Household $household, Recipe $recipe, int $listId): ShoppingList
    {
        $list = $household->shoppingLists()->with('items')->find($listId);

        if ($list === null) {
            throw (new ModelNotFoundException())->setModel(ShoppingList::class, [$listId]);
        }

        $present = $list->items->reject->isDone()->map(fn ($item) => mb_strtolower(trim($item->name)))->all();

        // All of a recipe's ingredients land on the list, or none do.
        DB::transaction(function () use ($recipe, $list, $present): void {
            foreach ($recipe->ingredients ?? [] as $ingredient) {
                $name = mb_strtolower(trim($ingredient['name']));

                if (in_array($name, $present, true)) {
                    continue;
                }

                $list->items()->create([
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'] ?? null,
                    'position' => (int) $list->items()->max('position') + 1,
                ]);
                $present[] = $name;
            }
        });

        return $list;
    }
}
