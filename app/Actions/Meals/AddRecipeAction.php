<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Inputs\RecipeInput;
use App\Models\Household;
use App\Models\Recipe;

class AddRecipeAction
{
    public function __construct(private readonly AddRecipeIngredientsToListAction $addIngredients) {}

    /** With a list picked, the new recipe's ingredients go straight onto it. */
    public function handle(Household $household, RecipeInput $input): Recipe
    {
        $recipe = $household->recipes()->create([
            'name' => $input->name,
            'description' => $input->description,
            'duration_label' => $input->durationLabel,
            'difficulty' => $input->difficulty,
            'tags' => $input->tags,
            'ingredients' => $input->ingredients,
            'tint' => $input->tint,
            'is_favourite' => $input->isFavourite,
        ]);

        if ($input->shoppingListId !== null) {
            $this->addIngredients->handle($household, $recipe, $input->shoppingListId);
        }

        return $recipe;
    }
}
