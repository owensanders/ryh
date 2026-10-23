<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Enums\AiMealPlanStatusEnum;
use App\Exceptions\MealPlanUnavailableException;
use App\Inputs\AiMealPlanAnswersInput;
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
     * @throws MealPlanUnavailableException
     */
    public function handle(Household $household, AiMealPlanAnswersInput $answers, User $requester): AiMealPlan
    {
        $plan = DB::transaction(function () use ($household, $answers, $requester): AiMealPlan {
            $household = $household->lockRow();

            $this->generate->ensureAvailable($household);

            if ($household->aiMealPlans()->where('status', AiMealPlanStatusEnum::Pending)->latest('id')->first()?->isInProgress() === true) {
                throw MealPlanUnavailableException::alreadyGenerating();
            }

            return $household->aiMealPlans()->create([
                'requested_by' => $requester->id,
                'status' => AiMealPlanStatusEnum::Pending,
                'answers' => $answers->toStored(),
            ]);
        });

        GenerateAiMealPlanJob::dispatch($plan->id);

        return $plan;
    }
}
