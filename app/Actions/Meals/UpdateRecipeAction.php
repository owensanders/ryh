<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\Recipe;

class UpdateRecipeAction
{
    /** @param  array<string, mixed>  $data  RecipeRequest's validated input; see AddRecipeAction. */
    public function handle(Recipe $recipe, array $data): Recipe
    {
        $recipe->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_label' => $data['duration_label'] ?? null,
            'difficulty' => $data['difficulty'] ?? null,
            'tags' => array_values($data['tags'] ?? []),
            'ingredients' => array_values($data['ingredients'] ?? []),
            'tint' => (int) ($data['tint'] ?? 0),
            'is_favourite' => (bool) ($data['is_favourite'] ?? false),
        ]);

        return $recipe->refresh();
    }
}
