<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * The meal planner for one week. Props that query are closures, so a partial
 * reload (the AI plan's poll, a moved meal) runs only the ones it asks for.
 * `recipes` is left out until asked for: only the meal dialog needs it.
 */
class MealPlannerScreenData extends Data
{
    /**
     * @param  Lazy|array<int, PlannerDayData>  $days
     * @param  Lazy|array<int, RecipeData>  $library
     * @param  Lazy|array<int, RecipeData>  $recipes
     * @param  Lazy|array<int, MemberData>  $members
     * @param  Lazy|AiMealPlanData|null  $aiPlan  The latest from the last day, if any: pending while its job runs.
     * @param  Lazy|array<int, ShoppingListData>  $shoppingLists  Where a recipe's ingredients can be sent.
     * @param  list<array{value: string, label: string}>  $tagOptions
     * @param  array{diets: list<string>, goals: list<string>, confidence: list<array{value: string, label: string}>, equipment: list<string>}  $aiOptions  What the AI plan form offers.
     */
    public function __construct(
        public string $weekOf,
        /** The household's today: the planner also shows other weeks, where no day is it. */
        public string $todayDate,
        public Lazy|array $days,
        public Lazy|array $library,
        public Lazy|int $recipeCount,
        public Lazy|array $recipes,
        public Lazy|array $members,
        public bool $aiConfigured,
        public bool $aiPaused,
        public ?string $aiAvailableFrom,
        public Lazy|int|null $aiSuggestedBudget,
        public Lazy|AiMealPlanData|null $aiPlan,
        public Lazy|array $shoppingLists,
        public array $tagOptions,
        public array $aiOptions,
    ) {}
}
