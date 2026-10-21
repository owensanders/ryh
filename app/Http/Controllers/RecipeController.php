<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Meals\AddRecipeAction;
use App\Actions\Meals\DeleteRecipeAction;
use App\Actions\Meals\UpdateRecipeAction;
use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Http\Requests\AddRecipeIngredientsRequest;
use App\Http\Requests\RecipeRequest;
use App\Models\Recipe;
use App\Traits\ResolvesHouseholdTrait;
use App\ViewModels\Meals\RecipeLibraryViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    use ResolvesHouseholdTrait;

    public function index(Request $request, RecipeLibraryViewModel $recipeLibrary): Response
    {
        return Inertia::render('Recipes', $recipeLibrary->toData($this->household($request)));
    }

    public function store(RecipeRequest $request, AddRecipeAction $add): RedirectResponse
    {
        $recipe = $add->handle($this->household($request), $request->validated());

        return back()->with('toast', "{$recipe->name} added");
    }

    public function update(RecipeRequest $request, Recipe $recipe, UpdateRecipeAction $update): RedirectResponse
    {
        $updated = $update->handle($recipe, $request->validated());

        return back()->with('toast', "{$updated->name} updated");
    }

    public function destroy(Recipe $recipe, DeleteRecipeAction $delete): RedirectResponse
    {
        $name = $recipe->name;
        $delete->handle($recipe);

        return back()->with('toast', "{$name} deleted");
    }

    public function addToShoppingList(
        AddRecipeIngredientsRequest $request,
        Recipe $recipe,
        AddRecipeIngredientsToListAction $addIngredients,
    ): RedirectResponse {
        $updated = $addIngredients->handle($this->household($request), $recipe, (int) $request->validated('shopping_list_id'));

        return back()->with('toast', "Ingredients added to {$updated->name}");
    }
}
