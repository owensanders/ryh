<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Ai\Agents\MealPlanAgent;
use App\Ai\AiConfig;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Models\Household;
use App\Models\Recipe;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\AiException;
use RuntimeException;

class GenerateAiMealPlanUseCase
{
    private const RECENT_RECIPES = 10;

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /**
     * @param  list<string>  $diets
     * @param  list<string>  $goals
     * @return list<array{name: string, description: string, duration_label: string, difficulty: string, tags: list<string>, ingredients: list<array{name: string, quantity: string}>}>
     */
    public function execute(
        Household $household,
        int $people,
        ?float $weeklyBudget,
        array $diets,
        string $avoid,
        array $goals,
    ): array {
        if (! AiConfig::isConfigured()) {
            throw new RuntimeException('AI meal planning is not set up yet.');
        }

        if (! $household->canGenerateAiMealPlan()) {
            throw new RuntimeException('Your household can generate one AI meal plan per week — check back soon.');
        }

        $avoidList = collect(explode(',', $avoid))->map(fn (string $item) => trim($item))->filter()->values()->all();
        $recentRecipeNames = $this->meals->allRecipes($household)
            ->sortByDesc('id')
            ->take(self::RECENT_RECIPES)
            ->map(fn (Recipe $recipe) => $recipe->name)
            ->values()
            ->all();

        $agent = new MealPlanAgent($people, $weeklyBudget, $diets, $avoidList, $goals, $recentRecipeNames);

        try {
            $response = $agent->prompt('Suggest this week\'s dinners.');
        } catch (AiException|RequestException $e) {
            report($e);

            throw new RuntimeException('We couldn\'t generate a meal plan right now — please try again in a few minutes.');
        }

        $this->households->update($household, ['ai_meal_plan_generated_at' => now()]);

        return $response['meals'];
    }
}
