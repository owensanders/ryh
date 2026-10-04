<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Ai\AiConfig;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Data\MemberData;
use App\Data\PlannedMealData;
use App\Data\PlannerDayData;
use App\Data\RecipeData;
use App\Models\Household;
use App\Models\PlannedMeal;
use Carbon\CarbonImmutable;

class GetMealPlannerUseCase
{
    private const LIBRARY_SIZE = 6;

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /**
     * @return array{
     *     weekOf: string,
     *     days: list<PlannerDayData>,
     *     library: list<RecipeData>,
     *     recipeCount: int,
     *     recipes: list<RecipeData>,
     *     members: list<MemberData>,
     *     aiConfigured: bool,
     *     aiAvailableFrom: ?string,
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
            'aiConfigured' => AiConfig::isConfigured(),
            'aiAvailableFrom' => $household->canGenerateAiMealPlan()
                ? null
                : $household->ai_meal_plan_generated_at->addWeek()->toDateString(),
        ];
    }
}
