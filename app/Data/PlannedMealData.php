<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\PlannedMeal;
use Spatie\LaravelData\Data;

class PlannedMealData extends Data
{
    /** @param array<int, string> $tags */
    public function __construct(
        public int $id,
        public int $recipeId,
        public string $name,
        public string $slot,
        public string $slotKey,
        public string $plannedOn,
        public ?string $duration,
        public ?string $difficulty,
        public ?string $description,
        public array $tags,
        public int $tint,
        public int $missingIngredients,
        public string $missingLabel,
        public ?MemberData $cook,
    ) {}

    public static function fromModel(PlannedMeal $meal): self
    {
        $missing = $meal->missing_ingredients;

        return new self(
            id: $meal->id,
            recipeId: $meal->recipe_id,
            name: $meal->recipe->name,
            slot: $meal->slot->label(),
            slotKey: $meal->slot->value,
            plannedOn: $meal->planned_on->toDateString(),
            duration: $meal->recipe->duration_label,
            difficulty: $meal->recipe->difficulty,
            description: $meal->recipe->description,
            tags: $meal->recipe->tags ?? [],
            tint: $meal->recipe->tint,
            missingIngredients: $missing,
            missingLabel: $missing === 0 ? 'All in' : "{$missing} missing",
            cook: $meal->cook !== null ? MemberData::fromModel($meal->cook) : null,
        );
    }
}
