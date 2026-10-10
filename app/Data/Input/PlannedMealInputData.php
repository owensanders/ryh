<?php

declare(strict_types=1);

namespace App\Data\Input;

use App\Enums\MealSlotEnum;
use Carbon\CarbonImmutable;

/**
 * A meal as the planner form submits it, validated by PlannedMealRequest:
 * an existing recipe by id, or a new one by name, plus any recipe details sent.
 */
final readonly class PlannedMealInputData
{
    public function __construct(
        public ?int $recipeId,
        public ?string $newRecipeName,
        public RecipeDetailsInputData $recipeDetails,
        public ?int $addIngredientsToListId,
        public CarbonImmutable $plannedOn,
        public MealSlotEnum $slot,
        public ?int $cookId,
    ) {}
}
