<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Ai\Agents\MealPlanAgent;
use App\Ai\AiConfig;
use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Enums\CookingConfidence;
use App\Models\Household;
use App\Models\Recipe;
use App\Notifications\AiCreditsExhaustedNotification;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use LogicException;
use RuntimeException;

class GenerateAiMealPlanUseCase
{
    private const RECENT_RECIPES = 10;

    private const LOCK_SECONDS = 120;

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /**
     * @param  array<int, string>  $diets
     * @param  array<int, string>  $goals
     * @param  array<int, string>  $equipment
     * @return array<int, array{name: string, description: string, duration_label: string, difficulty: string, estimated_cost: float, tags: array<int, string>, ingredients: array<int, array{name: string, quantity: string}>}>
     */
    public function execute(
        Household $household,
        int $people,
        ?float $weeklyBudget,
        array $diets,
        string $avoid,
        array $goals,
        CookingConfidence $confidence,
        array $equipment,
        string $useUp,
        string $notes,
    ): array {
        if (! AiConfig::isConfigured()) {
            throw new RuntimeException('AI meal planning is not set up yet.');
        }

        if (AiConfig::isPaused()) {
            throw new RuntimeException('AI meal planning is taking a break — you can still plan meals by hand.');
        }

        if (! $household->canGenerateAiMealPlan()) {
            throw new RuntimeException('Your household can generate one AI meal plan per week — check back soon.');
        }

        if ($this->households->countAiMealPlansSince(today()) >= (int) config('meal_planner.daily_cap')) {
            throw new RuntimeException('AI meal planning has hit its limit for today — try again tomorrow.');
        }

        // Two adults pressing Generate at once would otherwise both pass the
        // weekly check above and each pay for a plan.
        $lock = Cache::lock("ai-meal-plan:{$household->id}", self::LOCK_SECONDS);

        if (! $lock->get()) {
            throw new RuntimeException('A meal plan is already being generated for your household.');
        }

        try {
            $agent = new MealPlanAgent(
                $people,
                $weeklyBudget,
                $diets,
                $this->splitList($avoid),
                $goals,
                $this->recentRecipeNames($household),
                $confidence,
                $equipment,
                $this->splitList($useUp),
                trim($notes),
            );

            try {
                $response = $agent->prompt('Suggest this week\'s dinners.');
            } catch (InsufficientCreditsException $e) {
                $this->pauseForMissingCredit($e);

                throw new RuntimeException('AI meal planning is taking a break — you can still plan meals by hand. Your weekly plan hasn\'t been used.');
            } catch (AiException|RequestException $e) {
                report($e);

                throw new RuntimeException('We couldn\'t generate a meal plan right now — please try again in a few minutes.');
            }

            // MealPlanAgent declares a schema, so its answer is always structured.
            if (! $response instanceof StructuredAgentResponse) {
                throw new LogicException('MealPlanAgent answered without its structured schema.');
            }

            $this->households->update($household, ['ai_meal_plan_generated_at' => now()]);

            Log::info('AI meal plan generated', [
                'household_id' => $household->id,
                'input_tokens' => $response->usage->promptTokens,
                'output_tokens' => $response->usage->completionTokens,
            ]);

            return $response['meals'];
        } finally {
            $lock->release();
        }
    }

    /** @return array<int, string> */
    private function splitList(string $value): array
    {
        return collect(explode(',', $value))->map(fn (string $item) => trim($item))->filter()->values()->all();
    }

    /** @return array<int, string> */
    private function recentRecipeNames(Household $household): array
    {
        return $this->meals->allRecipes($household)
            ->sortByDesc('id')
            ->take(self::RECENT_RECIPES)
            ->map(fn (Recipe $recipe) => $recipe->name)
            ->values()
            ->all();
    }

    private function pauseForMissingCredit(InsufficientCreditsException $e): void
    {
        $firstAlert = AiConfig::pause();

        if (! $firstAlert) {
            return;
        }

        Log::critical('AI provider is out of credit; AI meal planning paused.', ['exception' => $e->getMessage()]);

        $email = config('meal_planner.alert_email');

        if (filled($email)) {
            Notification::route('mail', $email)->notify(new AiCreditsExhaustedNotification((string) config('ai.default')));
        }
    }
}
