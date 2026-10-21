<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\Household;
use App\Models\Recipe;

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

    /** The meal form's recipe fields => the recipe's columns. */
    private const array DETAIL_COLUMNS = [
        'new_recipe_description' => 'description',
        'new_recipe_duration_label' => 'duration_label',
        'new_recipe_difficulty' => 'difficulty',
        'new_recipe_tags' => 'tags',
        'new_recipe_ingredients' => 'ingredients',
        'new_recipe_tint' => 'tint',
        'new_recipe_is_favourite' => 'is_favourite',
    ];

    /** @param  array<string, mixed>  $data  PlannedMealRequest's validated input. */
    public function handle(Household $household, array $data): Recipe
    {
        $details = $this->sentDetails($data);

        if ($data['recipe_id'] !== null) {
            $recipe = $household->recipes()->findOrFail((int) $data['recipe_id']);

            // Only what was sent changes, so planning a recipe by id alone can't
            // blank its description, tags and so on.
            if ($details !== []) {
                $recipe->update($details);
            }

            return $recipe;
        }

        return $household->recipes()->create([
            'name' => $data['new_recipe_name'],
            ...self::NEW_RECIPE_DEFAULTS,
            ...$details,
        ]);
    }

    /**
     * Recipe columns for just the details the form sent.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    private function sentDetails(array $data): array
    {
        $details = [];

        foreach (self::DETAIL_COLUMNS as $field => $column) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            $details[$column] = match ($column) {
                'tags', 'ingredients' => array_values(is_array($value) ? $value : []),
                'tint' => (int) ($value ?? 0),
                'is_favourite' => (bool) $value,
                default => $value,
            };
        }

        return $details;
    }
}
