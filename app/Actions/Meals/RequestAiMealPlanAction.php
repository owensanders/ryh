<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Enums\AiMealPlanStatusEnum;
use App\Exceptions\MealPlanUnavailableException;
use App\Jobs\GenerateAiMealPlanJob;
use App\Models\AiMealPlan;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Asking for this week's AI meal plan: checked now, written by a queued job. */
class RequestAiMealPlanAction
{
    public function __construct(
        private readonly GenerateAiMealPlanAction $generate,
    ) {}

    /**
     * Checked under the household's lock, so two adults pressing Generate at
     * once get one plan between them, not one each.
     *
     * @param  array<string, mixed>  $data  GenerateAiMealPlanRequest's validated input: people,
     *                                      budget, diets, avoid, goals, confidence, equipment,
     *                                      use_up, notes.
     *
     * @throws MealPlanUnavailableException
     */
    public function handle(Household $household, array $data, User $requester): AiMealPlan
    {
        $plan = DB::transaction(function () use ($household, $data, $requester): AiMealPlan {
            $household = $household->lockRow();

            $this->generate->ensureAvailable($household);

            if ($household->aiMealPlans()->where('status', AiMealPlanStatusEnum::Pending)->latest('id')->first()?->isInProgress() === true) {
                throw MealPlanUnavailableException::alreadyGenerating();
            }

            return $household->aiMealPlans()->create([
                'requested_by' => $requester->id,
                'status' => AiMealPlanStatusEnum::Pending,
                'answers' => $data,
            ]);
        });

        GenerateAiMealPlanJob::dispatch($plan->id);

        return $plan;
    }
}
