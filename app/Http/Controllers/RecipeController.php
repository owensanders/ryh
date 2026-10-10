<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Repositories\ShoppingRepositoryInterface;
use App\Enums\RecipeTagEnum;
use App\Http\Requests\AddRecipeIngredientsRequest;
use App\Http\Requests\RecipeRequest;
use App\Models\Recipe;
use App\Traits\ResolvesHouseholdTrait;
use App\UseCases\Meals\AddRecipeUseCase;
use App\UseCases\Meals\DeleteRecipeUseCase;
use App\UseCases\Meals\GetRecipeLibraryUseCase;
use App\UseCases\Meals\UpdateRecipeUseCase;
use App\UseCases\Shopping\AddRecipeIngredientsToListUseCase;
use App\UseCases\Shopping\GetShoppingListOptionsUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    use ResolvesHouseholdTrait;

    public function __construct(private readonly ShoppingRepositoryInterface $shopping) {}

    public function index(Request $request, GetRecipeLibraryUseCase $getLibrary, GetShoppingListOptionsUseCase $getShoppingLists): Response
    {
        $household = $this->household($request);

        return Inertia::render('Recipes', [
            'recipes' => $getLibrary->execute($household),
            'tagOptions' => RecipeTagEnum::options(),
            'shoppingLists' => $getShoppingLists->execute($household),
        ]);
    }

    public function store(RecipeRequest $request, AddRecipeUseCase $add, AddRecipeIngredientsToListUseCase $addIngredients): RedirectResponse
    {
        $household = $this->household($request);
        $recipe = $add->execute($household, $request->recipeAttributes());

        $listId = $request->validated('shopping_list_id');

        if ($listId !== null) {
            $list = $this->shopping->listsFor($household)->firstWhere('id', $listId);

            if ($list !== null) {
                $addIngredients->execute(Recipe::findOrFail($recipe->id), $list);
            }
        }

        return back()->with('toast', "{$recipe->name} added");
    }

    public function update(RecipeRequest $request, Recipe $recipe, UpdateRecipeUseCase $update): RedirectResponse
    {
        $updated = $update->execute($recipe, $request->recipeAttributes());

        return back()->with('toast', "{$updated->name} updated");
    }

    public function destroy(Recipe $recipe, DeleteRecipeUseCase $delete): RedirectResponse
    {
        $name = $recipe->name;
        $delete->execute($recipe);

        return back()->with('toast', "{$name} deleted");
    }

    public function addToShoppingList(
        AddRecipeIngredientsRequest $request,
        Recipe $recipe,
        AddRecipeIngredientsToListUseCase $addIngredients,
    ): RedirectResponse {
        $list = $this->shopping->listsFor($this->household($request))
            ->firstWhere('id', $request->validated('shopping_list_id'));

        abort_if($list === null, 404);

        $updated = $addIngredients->execute($recipe, $list);

        return back()->with('toast', "Ingredients added to {$updated->name}");
    }
}
