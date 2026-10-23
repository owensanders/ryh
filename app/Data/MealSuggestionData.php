<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Recipe;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * A suggested dinner — from the AI, or (with recipeId) from the household's
 * library. The library-only fields are left out entirely for an AI suggestion.
 */
class MealSuggestionData extends Data
{
    /**
     * @param  list<string>  $tags
     * @param  list<array{name: string, quantity: string|null}>  $ingredients
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $durationLabel,
        public ?string $difficulty,
        public ?float $estimatedCost,
        public array $tags,
        public array $ingredients,
        public int|Optional $recipeId = new Optional(),
        public int|Optional $tint = new Optional(),
        public bool|Optional $isFavourite = new Optional(),
    ) {}

    public static function fromRecipe(Recipe $recipe): self
    {
        return new self(
            name: $recipe->name,
            description: $recipe->description,
            durationLabel: $recipe->duration_label,
            difficulty: $recipe->difficulty,
            estimatedCost: null,
            tags: $recipe->tags ?? [],
            ingredients: $recipe->ingredients ?? [],
            recipeId: $recipe->id,
            tint: $recipe->tint,
            isFavourite: $recipe->is_favourite,
        );
    }

    /**
     * MealPlanAgent's schema keeps snake_case, which is what the model is asked for.
     *
     * @param  array{name: string, description: string, duration_label: string, difficulty: string, estimated_cost: float, tags: array<int, string>, ingredients: array<int, array{name: string, quantity: string}>}  $meal
     */
    public static function fromAi(array $meal): self
    {
        return new self(
            name: $meal['name'],
            description: $meal['description'],
            durationLabel: $meal['duration_label'],
            difficulty: $meal['difficulty'],
            estimatedCost: $meal['estimated_cost'],
            tags: array_values($meal['tags']),
            ingredients: array_values($meal['ingredients']),
        );
    }
}
