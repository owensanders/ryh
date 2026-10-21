<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Recipe;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * A suggested dinner — from the AI, or (with recipe_id) from the household's
 * library. Mirrors `AiMealSuggestion` in resources/js/types/ryh.ts; the
 * library-only fields are left out entirely for an AI suggestion.
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
        public ?string $duration_label,
        public ?string $difficulty,
        public ?float $estimated_cost,
        public array $tags,
        public array $ingredients,
        public int|Optional $recipe_id = new Optional(),
        public int|Optional $tint = new Optional(),
        public bool|Optional $is_favourite = new Optional(),
    ) {}

    public static function fromRecipe(Recipe $recipe): self
    {
        return new self(
            name: $recipe->name,
            description: $recipe->description,
            duration_label: $recipe->duration_label,
            difficulty: $recipe->difficulty,
            estimated_cost: null,
            tags: $recipe->tags ?? [],
            ingredients: $recipe->ingredients ?? [],
            recipe_id: $recipe->id,
            tint: $recipe->tint,
            is_favourite: $recipe->is_favourite,
        );
    }

    /** @param  array{name: string, description: string, duration_label: string, difficulty: string, estimated_cost: float, tags: array<int, string>, ingredients: array<int, array{name: string, quantity: string}>}  $meal */
    public static function fromAi(array $meal): self
    {
        return new self(
            name: $meal['name'],
            description: $meal['description'],
            duration_label: $meal['duration_label'],
            difficulty: $meal['difficulty'],
            estimated_cost: $meal['estimated_cost'],
            tags: array_values($meal['tags']),
            ingredients: array_values($meal['ingredients']),
        );
    }
}
