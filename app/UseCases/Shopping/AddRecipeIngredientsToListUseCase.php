<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Data\ShoppingListData;
use App\Models\Recipe;
use App\Models\ShoppingList;
use Illuminate\Support\Facades\DB;

class AddRecipeIngredientsToListUseCase
{
    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function execute(Recipe $recipe, ShoppingList $list): ShoppingListData
    {
        $present = $list->items->reject->isDone()->map(fn ($item) => mb_strtolower(trim($item->name)))->all();

        // All of a recipe's ingredients land on the list, or none do.
        DB::transaction(function () use ($recipe, $list, $present): void {
            foreach ($recipe->ingredients ?? [] as $ingredient) {
                $name = mb_strtolower(trim($ingredient['name']));

                if (in_array($name, $present, true)) {
                    continue;
                }

                $this->shopping->addItem($list, $ingredient['name'], $ingredient['quantity'] ?? null);
                $present[] = $name;
            }
        });

        return ShoppingListData::fromModel($list->refresh());
    }
}
