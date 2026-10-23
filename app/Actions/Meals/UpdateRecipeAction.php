<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Inputs\RecipeInput;
use App\Models\Recipe;

class UpdateRecipeAction
{
    public function handle(Recipe $recipe, RecipeInput $input): Recipe
    {
        $recipe->update([
            'name' => $input->name,
            'description' => $input->description,
            'duration_label' => $input->durationLabel,
            'difficulty' => $input->difficulty,
            'tags' => $input->tags,
            'ingredients' => $input->ingredients,
            'tint' => $input->tint,
            'is_favourite' => $input->isFavourite,
        ]);

        return $recipe->refresh();
    }
}
