<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Inputs\PlannedMealInput;
use App\Models\Household;
use App\Models\Recipe;
use Spatie\LaravelData\Optional;

/**
 * The recipe a planned meal is for: an existing one picked by id, or one
 * named on the meal form and created on the spot. Shared by adding and
 * editing a meal.
 */
class ResolvePlannedRecipeAction
{
    /** What a recipe created from the meal form starts with, before any details sent with it. */
    private const array NEW_RECIPE_DEFAULTS = [
        'description' => null,
        'duration_label' => null,
        'difficulty' => null,
        'tags' => [],
        'ingredients' => [],
        'tint' => 0,
        'is_favourite' => false,
    ];

    public function handle(Household $household, PlannedMealInput $input): Recipe
    {
        $details = $this->sentDetails($input);

        if ($input->recipeId !== null) {
            $recipe = $household->recipes()->findOrFail($input->recipeId);

            // Only what was sent changes, so planning a recipe by id alone can't
            // blank its description, tags and so on.
            if ($details !== []) {
                $recipe->update($details);
            }

            return $recipe;
        }

        return $household->recipes()->create([
            'name' => $input->newRecipeName,
            ...self::NEW_RECIPE_DEFAULTS,
            ...$details,
        ]);
    }

    /**
     * Recipe columns for just the details the form sent.
     *
     * @return array<string, mixed>
     */
    private function sentDetails(PlannedMealInput $input): array
    {
        $details = [
            'description' => $input->newRecipeDescription,
            'duration_label' => $input->newRecipeDurationLabel,
            'difficulty' => $input->newRecipeDifficulty,
            'tags' => $input->newRecipeTags,
            'ingredients' => $input->newRecipeIngredients,
            'tint' => $input->newRecipeTint instanceof Optional ? $input->newRecipeTint : ($input->newRecipeTint ?? 0),
            'is_favourite' => $input->newRecipeIsFavourite,
        ];

        return array_filter($details, fn (mixed $value): bool => ! $value instanceof Optional);
    }
}
