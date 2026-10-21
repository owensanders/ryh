<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Actions\Shopping\AddRecipeIngredientsToListAction;
use App\Models\Household;
use App\Models\Recipe;

class AddRecipeAction
{
    public function __construct(private readonly AddRecipeIngredientsToListAction $addIngredients) {}

    /**
     * With a list picked, the new recipe's ingredients go straight onto it.
     *
     * @param  array<string, mixed>  $data  RecipeRequest's validated input: name, description,
     *                                      duration_label, difficulty, tags, ingredients, tint,
     *                                      is_favourite, shopping_list_id.
     */
    public function handle(Household $household, array $data): Recipe
    {
        $recipe = $household->recipes()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_label' => $data['duration_label'] ?? null,
            'difficulty' => $data['difficulty'] ?? null,
            'tags' => array_values($data['tags'] ?? []),
            'ingredients' => array_values($data['ingredients'] ?? []),
            'tint' => (int) ($data['tint'] ?? 0),
            'is_favourite' => (bool) ($data['is_favourite'] ?? false),
        ]);

        if ($data['shopping_list_id'] !== null) {
            $this->addIngredients->handle($household, $recipe, $data['shopping_list_id']);
        }

        return $recipe;
    }
}
