<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Ai\Agents\MealPlanAgent;
use App\Ai\AiConfigService;
use App\Data\MealSuggestionData;
use App\Enums\CookingConfidenceEnum;
use App\Exceptions\MealPlanUnavailableException;
use App\Models\Household;
use App\Models\Recipe;
use App\Notifications\AiCreditsExhaustedNotification;
use App\Repositories\HouseholdRepository;
use App\Repositories\MealPlanRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use LogicException;

class GenerateAiMealPlanUseCase
{
    private const int RECENT_RECIPES = 10;

    private const int LOCK_SECONDS = 120;

    public function __construct(
        private readonly MealPlanRepository $meals,
        private readonly HouseholdRepository $households,
    ) {}

    /**
     * @param  array<int, string>  $diets
     * @param  array<int, string>  $goals
     * @param  array<int, string>  $equipment
     *
     * @return array<int, MealSuggestionData>
     */
    public function execute(
        Household $household,
        int $people,
        ?float $weeklyBudget,
        array $diets,
        string $avoid,
        array $goals,
        CookingConfidenceEnum $confidence,
        array $equipment,
        string $useUp,
        string $notes,
    ): array {
        if (! AiConfigService::isConfigured()) {
            throw MealPlanUnavailableException::notConfigured();
        }

        if (AiConfigService::isPaused()) {
            throw MealPlanUnavailableException::paused();
        }

        if (! $household->canGenerateAiMealPlan()) {
            throw MealPlanUnavailableException::weeklyLimitReached();
        }

        if ($this->households->countAiMealPlansSince(today()) >= (int) config('meal_planner.daily_cap')) {
            throw MealPlanUnavailableException::dailyCapReached();
        }

        // Two adults pressing Generate at once would otherwise both pass the
        // weekly check above and each pay for a plan.
        $lock = Cache::lock("ai-meal-plan:{$household->id}", self::LOCK_SECONDS);

        if (! $lock->get()) {
            throw MealPlanUnavailableException::alreadyGenerating();
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

                throw MealPlanUnavailableException::outOfCredit($e);
            } catch (AiException|ConnectionException|RequestException $e) {
                report($e);

                throw MealPlanUnavailableException::providerFailed($e);
            }

            // MealPlanAgent declares a schema, so its answer is always structured.
            if (! $response instanceof StructuredAgentResponse) {
                throw new LogicException('MealPlanAgent answered without its structured schema.');
            }

            $this->households->markAiMealPlanGenerated($household);

            Log::info('AI meal plan generated', [
                'household_id' => $household->id,
                'input_tokens' => $response->usage->promptTokens,
                'output_tokens' => $response->usage->completionTokens,
            ]);

            return array_map(MealSuggestionData::fromAi(...), $response['meals']);
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
        $firstAlert = AiConfigService::pause();

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
