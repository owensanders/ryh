<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Ai\Agents\MealPlanAgent;
use App\Ai\AiConfigService;
use App\Ai\MealSuggestionCheckService;
use App\Data\MealSuggestionData;
use App\Enums\AiMealPlanStatusEnum;
use App\Events\AiMealPlanFinishedEvent;
use App\Exceptions\MealPlanUnavailableException;
use App\Inputs\AiMealPlanAnswersInput;
use App\Models\AiMealPlan;
use App\Models\Household;
use App\Notifications\AiCreditsExhaustedNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use LogicException;

/**
 * Writes an AI meal plan someone asked for (RequestAiMealPlanAction), in a
 * queued job: the provider takes up to a minute, too long to hold a request.
 */
class GenerateAiMealPlanAction
{
    private const int RECENT_RECIPES = 10;

    private const int LOCK_SECONDS = 180;

    public function __construct(private readonly MealSuggestionCheckService $check) {}

    /**
     * Whether the household may have a plan now. Asked when the request comes
     * in, for a quick answer, and again by the job, under its lock, before any
     * money is spent.
     *
     * @throws MealPlanUnavailableException
     */
    public function ensureAvailable(Household $household): void
    {
        if (! AiConfigService::isConfigured()) {
            throw MealPlanUnavailableException::notConfigured();
        }

        if (AiConfigService::isPaused()) {
            throw MealPlanUnavailableException::paused();
        }

        if (! $household->canGenerateAiMealPlan()) {
            throw MealPlanUnavailableException::weeklyLimitReached();
        }

        if (Household::query()->where('ai_meal_plan_generated_at', '>=', today())->count() >= (int) config('meal_planner.daily_cap')) {
            throw MealPlanUnavailableException::dailyCapReached();
        }
    }

    /**
     * Fills in the plan's meals, or its error when the provider or the
     * household's allowance says no, and tells whoever asked. Anything
     * unexpected is left to GenerateAiMealPlanJob::failed().
     */
    public function handle(AiMealPlan $plan): void
    {
        $household = $plan->household()->firstOrFail();

        // One plan at a time per household, whatever the queue does.
        $lock = Cache::lock("ai-meal-plan:{$household->id}", self::LOCK_SECONDS);

        if (! $lock->get()) {
            $this->finish($plan, MealPlanUnavailableException::alreadyGenerating());

            return;
        }

        try {
            try {
                $household->refresh();
                $this->ensureAvailable($household);
                $meals = $this->ask($household, AiMealPlanAnswersInput::from($plan->answers));
            } catch (MealPlanUnavailableException $e) {
                $this->finish($plan, $e);

                return;
            }

            // Saved before the lock goes, so the next plan sees this week's used.
            DB::transaction(function () use ($household, $plan, $meals): void {
                $household->update(['ai_meal_plan_generated_at' => now()]);
                $plan->update([
                    'status' => AiMealPlanStatusEnum::Ready,
                    'meals' => array_map(fn (MealSuggestionData $meal) => $meal->toArray(), $meals),
                    'finished_at' => now(),
                ]);
            });
        } finally {
            $lock->release();
        }

        AiMealPlanFinishedEvent::dispatchFor($plan);
    }

    /** Marks the plan failed with a message for the person, and tells them. */
    public function finish(AiMealPlan $plan, MealPlanUnavailableException $reason): void
    {
        $plan->update([
            'status' => AiMealPlanStatusEnum::Failed,
            'error' => $reason->getMessage(),
            'finished_at' => now(),
        ]);

        AiMealPlanFinishedEvent::dispatchFor($plan);
    }

    /**
     * @return array<int, MealSuggestionData>
     *
     * @throws MealPlanUnavailableException
     */
    private function ask(Household $household, AiMealPlanAnswersInput $answers): array
    {
        $agent = new MealPlanAgent(
            $answers->people,
            $answers->budget,
            $answers->diets,
            $this->splitList($answers->avoid ?? ''),
            $answers->goals,
            $this->recentRecipeNames($household),
            $answers->confidence,
            $answers->equipment,
            $this->splitList($answers->useUp ?? ''),
            trim($answers->notes ?? ''),
        );

        try {
            $response = $agent->prompt($agent->message());
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

        // Only what passes the check is shown; the rest is dropped, and counted here.
        $suggested = is_array($response['meals'] ?? null) ? $response['meals'] : [];
        $usable = $this->check->usable($suggested);

        Log::info('AI meal plan generated', [
            'household_id' => $household->id,
            'input_tokens' => $response->usage->promptTokens,
            'output_tokens' => $response->usage->completionTokens,
            'meals_dropped' => count($suggested) - count($usable),
        ]);

        if ($usable === []) {
            throw MealPlanUnavailableException::unusableAnswer();
        }

        return array_map(MealSuggestionData::fromAi(...), $usable);
    }

    /** @return array<int, string> */
    private function splitList(string $value): array
    {
        return collect(explode(',', $value))->map(fn (string $item) => trim($item))->filter()->values()->all();
    }

    /** @return array<int, string> */
    private function recentRecipeNames(Household $household): array
    {
        return $household->recipes()->latest('id')->limit(self::RECENT_RECIPES)->pluck('name')->all();
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
