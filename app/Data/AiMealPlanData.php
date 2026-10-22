<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AiMealPlanStatusEnum;
use App\Exceptions\MealPlanUnavailableException;
use App\Models\AiMealPlan;
use RuntimeException;
use Spatie\LaravelData\Data;

/** The household's latest AI meal plan, so the dialog can wait for it, show it, or say why it failed. */
class AiMealPlanData extends Data
{
    /** @param  array<int, MealSuggestionData>  $meals */
    public function __construct(
        public int $id,
        public AiMealPlanStatusEnum $status,
        public array $meals,
        public ?string $error,
    ) {}

    public static function fromModel(AiMealPlan $plan): self
    {
        // Its worker died with it pending; it never used the week's allowance.
        if ($plan->isStale()) {
            return new self($plan->id, AiMealPlanStatusEnum::Failed, [], MealPlanUnavailableException::providerFailed(new RuntimeException('Stale plan.'))->getMessage());
        }

        return new self(
            id: $plan->id,
            status: $plan->status,
            meals: array_map(fn (array $meal) => MealSuggestionData::from($meal), $plan->meals ?? []),
            error: $plan->error,
        );
    }
}
