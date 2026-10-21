<?php

declare(strict_types=1);

namespace App\UseCases\Shopping;

use App\Data\ShoppingListData;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Repositories\ShoppingRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AddRecipeIngredientsToListUseCase
{
    public function __construct(private readonly ShoppingRepository $shopping) {}

    /**
     * @return ShoppingListData The updated list.
     *
     * @throws ModelNotFoundException When the list isn't one of the household's.
     */
    public function execute(Household $household, Recipe $recipe, int $listId): ShoppingListData
    {
        $list = $this->shopping->findList($household, $listId);

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

                $this->shopping->addItem($list, $ingredient['name'], $ingredient['quantity'] ?? null);
                $present[] = $name;
            }
        });

        return ShoppingListData::fromModel($this->shopping->findList($household, $listId) ?? $list);
    }
}
