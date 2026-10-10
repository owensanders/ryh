<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The meal planner for one week. */
class MealPlannerScreenData extends Data
{
    /**
     * @param  array<int, PlannerDayData>  $days
     * @param  array<int, RecipeData>  $library
     * @param  array<int, RecipeData>  $recipes
     * @param  array<int, MemberData>  $members
     */
    public function __construct(
        public string $weekOf,
        public array $days,
        public array $library,
        public int $recipeCount,
        public array $recipes,
        public array $members,
        public bool $aiConfigured,
        public bool $aiPaused,
        public ?string $aiAvailableFrom,
        public ?int $aiSuggestedBudget,
    ) {}
}
