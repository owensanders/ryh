<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\Recipe;

class DeleteRecipeAction
{
    public function handle(Recipe $recipe): void
    {
        $recipe->delete();
    }
}
