<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Ai\AiConfigService;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Data\MemberData;
use App\Data\PlannedMealData;
use App\Data\PlannerDayData;
use App\Data\RecipeData;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\PlannedMeal;
use Carbon\CarbonImmutable;

class GetMealPlannerUseCase
{
    private const int LIBRARY_SIZE = 6;

    private const string FOOD_BUDGET_LABEL = '/food|grocer|shopping/i';

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /**
     * @return array{
     *     weekOf: string,
     *     days: array<int, PlannerDayData>,
     *     library: array<int, RecipeData>,
     *     recipeCount: int,
     *     recipes: array<int, RecipeData>,
     *     members: array<int, MemberData>,
     *     aiConfigured: bool,
     *     aiPaused: bool,
     *     aiAvailableFrom: ?string,
     *     aiSuggestedBudget: ?int,
     * }
     */
    public function execute(Household $household, CarbonImmutable $anyDayInWeek): array
    {
        $start = $anyDayInWeek->startOfWeek();
        $end = $start->addDays(6);
        $today = CarbonImmutable::today();

        $planned = $this->meals->between($household, $start, $end)
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

        return [
            'weekOf' => $start->format('j F'),
            'days' => $days,
            'library' => $this->meals->favouriteRecipes($household, self::LIBRARY_SIZE)
                ->map(RecipeData::fromModel(...))
                ->values()
                ->all(),
            'recipeCount' => $this->meals->countRecipes($household),
            'recipes' => $this->meals->allRecipes($household)
                ->map(RecipeData::fromModel(...))
                ->values()
                ->all(),
            'members' => $this->households->activeMembers($household)
                ->map(MemberData::fromModel(...))
                ->values()
                ->all(),
            'aiConfigured' => AiConfigService::isConfigured(),
            'aiPaused' => AiConfigService::isPaused(),
            'aiAvailableFrom' => $household->canGenerateAiMealPlan()
                ? null
                : $household->ai_meal_plan_generated_at?->addWeek()->toDateString(),
            'aiSuggestedBudget' => $this->suggestedWeeklyFoodBudget($household, $today),
        ];
    }

    /**
     * Weekly £ from the food budget category, to prefill the AI planner. Uses
     * the latest month that has one, since the current month's budget often
     * isn't set up yet early in the month.
     */
    private function suggestedWeeklyFoodBudget(Household $household, CarbonImmutable $today): ?int
    {
        $food = $this->households->budgetCategoriesUpTo($household, $today)
            ->first(fn (BudgetCategory $category) => preg_match(self::FOOD_BUDGET_LABEL, $category->label) === 1);

        if ($food === null || $food->budgeted_pence <= 0) {
            return null;
        }

        return (int) round($food->budgeted_pence / 100 * 12 / 52);
    }
}
