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
use App\Models\ShoppingList;
use Carbon\CarbonImmutable;

class MealPlannerViewModel
{
    private const int LIBRARY_SIZE = 6;

    private const string FOOD_BUDGET_LABEL = '/food|grocer|shopping/i';

    public function toData(Household $household, CarbonImmutable $anyDayInWeek): MealPlannerScreenData
    {
        $start = $anyDayInWeek->startOfWeek();
        $end = $start->addDays(6);
        $today = $household->today();

        $planned = $household->plannedMeals()
            ->with(['recipe', 'cook'])
            ->whereBetween('planned_on', [$start->toDateString(), $end->toDateString()])
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
                meals: $planned->get($key, collect())->map(PlannedMealData::fromModel(...))->values()->all(),
            );
        }

        return new MealPlannerScreenData(
            weekOf: $start->format('j F'),
            days: $days,
            library: $household->recipes()->where('is_favourite', true)->orderBy('name')->limit(self::LIBRARY_SIZE)->get()
                ->map(RecipeData::fromModel(...))
                ->values()
                ->all(),
            recipeCount: $household->recipes()->count(),
            recipes: $household->recipes()->inLibraryOrder()->get()
                ->map(RecipeData::fromModel(...))
                ->values()
                ->all(),
            members: $household->activeMembers()->get()
                ->map(MemberData::fromModel(...))
                ->values()
                ->all(),
            aiConfigured: AiConfigService::isConfigured(),
            aiPaused: AiConfigService::isPaused(),
            aiAvailableFrom: $household->canGenerateAiMealPlan()
                ? null
                : $household->ai_meal_plan_generated_at?->addWeek()->toDateString(),
            aiSuggestedBudget: $this->suggestedWeeklyFoodBudget($household, $today),
            aiPlan: $this->latestAiPlan($household),
            shoppingLists: $household->shoppingLists()->with('items')->get()
                ->map(fn (ShoppingList $list) => ShoppingListData::fromModel($list, withItems: false))
                ->values()
                ->all(),
            tagOptions: RecipeTagEnum::options(),
            aiOptions: [
                'diets' => MealDietEnum::values(),
                'goals' => MealGoalEnum::values(),
                'confidence' => CookingConfidenceEnum::options(),
                'equipment' => KitchenEquipmentEnum::values(),
            ],
        );
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
