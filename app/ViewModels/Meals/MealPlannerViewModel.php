<?php

declare(strict_types=1);

namespace App\ViewModels\Meals;

use App\Ai\AiConfigService;
use App\Data\AiMealPlanData;
use App\Data\MealPlannerScreenData;
use App\Data\MemberData;
use App\Data\PlannedMealData;
use App\Data\PlannerDayData;
use App\Data\RecipeData;
use App\Data\ShoppingListData;
use App\Enums\CookingConfidenceEnum;
use App\Enums\KitchenEquipmentEnum;
use App\Enums\MealDietEnum;
use App\Enums\MealGoalEnum;
use App\Enums\RecipeTagEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Lazy;

class MealPlannerViewModel
{
    private const int LIBRARY_SIZE = 6;

    private const string FOOD_BUDGET_LABEL = '/food|grocer|shopping/i';

    public function toData(Household $household, User $viewer, CarbonImmutable $anyDayInWeek): MealPlannerScreenData
    {
        $start = $anyDayInWeek->startOfWeek();
        $today = $household->today();

        return new MealPlannerScreenData(
            weekOf: $start->format('j F'),
            days: Lazy::closure(fn () => $this->days($household, $viewer, $start, $today)),
            library: Lazy::closure(fn () => $household->recipes()->where('is_favourite', true)->orderBy('name')->limit(self::LIBRARY_SIZE)->get()
                ->map(fn (Recipe $recipe) => RecipeData::fromModel($recipe, $viewer))
                ->values()
                ->all()),
            recipeCount: Lazy::closure(fn () => $household->recipes()->count()),
            recipes: Lazy::inertia(fn () => $household->recipes()->inLibraryOrder()->get()
                ->map(fn (Recipe $recipe) => RecipeData::fromModel($recipe, $viewer))
                ->values()
                ->all()),
            members: Lazy::closure(fn () => $household->activeMembers()->get()
                ->map(MemberData::fromModel(...))
                ->values()
                ->all()),
            aiConfigured: AiConfigService::isConfigured(),
            aiPaused: AiConfigService::isPaused(),
            aiAvailableFrom: $household->canGenerateAiMealPlan()
                ? null
                : $household->ai_meal_plan_generated_at?->addWeek()->toDateString(),
            aiSuggestedBudget: Lazy::closure(fn () => $this->suggestedWeeklyFoodBudget($household, $today)),
            aiPlan: Lazy::closure(fn () => $this->latestAiPlan($household)),
            shoppingLists: Lazy::closure(fn () => $household->shoppingLists()->withItemCounts()->get()
                ->map(fn (ShoppingList $list) => ShoppingListData::summary($list, $viewer))
                ->values()
                ->all()),
            tagOptions: RecipeTagEnum::options(),
            aiOptions: [
                'diets' => MealDietEnum::values(),
                'goals' => MealGoalEnum::values(),
                'confidence' => CookingConfidenceEnum::options(),
                'equipment' => KitchenEquipmentEnum::values(),
            ],
        );
    }

    /** @return list<PlannerDayData> The week's seven days, Monday first, with their meals. */
    private function days(Household $household, User $viewer, CarbonImmutable $start, CarbonImmutable $today): array
    {
        $planned = $household->plannedMeals()
            ->with(['recipe', 'cook'])
            ->whereBetween('planned_on', [$start->toDateString(), $start->addDays(6)->toDateString()])
            ->orderBy('planned_on')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (PlannedMeal $meal) => $meal->planned_on->toDateString());

        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $start->addDays($offset);
            $key = $day->toDateString();

            $days[] = new PlannerDayData(
                date: $key,
                dayLabel: $day->format('D'),
                dateLabel: $day->format('d/m'),
                isToday: $day->isSameDay($today),
                meals: $planned->get($key, collect())->map(fn (PlannedMeal $meal) => PlannedMealData::fromModel($meal, $viewer))->values()->all(),
            );
        }

        return $days;
    }

    /**
     * Weekly £ from the food budget category, to prefill the AI planner. Uses
     * the latest month that has one, since the current month's budget often
     * isn't set up yet early in the month.
     */
    private function suggestedWeeklyFoodBudget(Household $household, CarbonImmutable $today): ?int
    {
        $food = $household->budgetCategories()
            ->whereDate('month', '<=', $today->startOfMonth()->toDateString())
            ->orderByDesc('month')
            ->get()
            ->first(fn (BudgetCategory $category) => preg_match(self::FOOD_BUDGET_LABEL, $category->label) === 1);

        if ($food === null || $food->budgeted_pence <= 0) {
            return null;
        }

        return (int) round($food->budgeted_pence / 100 * 12 / 52);
    }

    private function latestAiPlan(Household $household): ?AiMealPlanData
    {
        $plan = $household->aiMealPlans()->where('created_at', '>=', now()->subDay())->latest('id')->first();

        return $plan !== null ? AiMealPlanData::fromModel($plan) : null;
    }
}
