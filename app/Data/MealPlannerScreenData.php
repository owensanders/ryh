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
     * @param  array<int, ShoppingListData>  $shoppingLists  Where a recipe's ingredients can be sent.
     * @param  list<array{value: string, label: string}>  $tagOptions
     * @param  array{diets: list<string>, goals: list<string>, confidence: list<array{value: string, label: string}>, equipment: list<string>}  $aiOptions  What the AI plan form offers.
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
        public array $shoppingLists,
        public array $tagOptions,
        public array $aiOptions,
    ) {}
}
