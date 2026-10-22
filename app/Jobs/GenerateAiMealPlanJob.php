<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Meals\GenerateAiMealPlanAction;
use App\Exceptions\MealPlanUnavailableException;
use App\Models\AiMealPlan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/** Writes one AI meal plan in the background. */
class GenerateAiMealPlanJob implements ShouldQueue
{
    use Queueable;

    /** Never retried: a second attempt could pay the provider twice. */
    public int $tries = 1;

    /** Under AiMealPlan::STALE_AFTER_SECONDS, so a plan is only called stale once its job can't still be running. */
    public int $timeout = 170;

    public function __construct(public readonly int $planId) {}

    public function handle(GenerateAiMealPlanAction $generate): void
    {
        $plan = AiMealPlan::query()->find($this->planId);

        if ($plan !== null) {
            $generate->handle($plan);
        }
    }

    /** Something unexpected: the person gets the general message, never the exception's own. */
    public function failed(?Throwable $exception): void
    {
        $plan = AiMealPlan::query()->find($this->planId);

        if ($plan !== null) {
            app(GenerateAiMealPlanAction::class)->finish($plan, MealPlanUnavailableException::providerFailed($exception ?? new RuntimeException('The job failed without an exception.')));
        }
    }
}
