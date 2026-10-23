<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Enums\CookingConfidenceEnum;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The AI meal plan form's answers, as GenerateAiMealPlanRequest validated
 * them. Stored on the plan (toStored()) for its queued job to read back.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class AiMealPlanAnswersInput extends Dto
{
    /**
     * @param  list<string>  $diets  MealDietEnum values.
     * @param  list<string>  $goals  MealGoalEnum values.
     * @param  list<string>  $equipment  KitchenEquipmentEnum values.
     */
    public function __construct(
        public readonly int $people,
        public readonly CookingConfidenceEnum $confidence,
        /** Pounds a week. */
        public readonly ?float $budget = null,
        public readonly array $diets = [],
        public readonly ?string $avoid = null,
        public readonly array $goals = [],
        public readonly array $equipment = [],
        public readonly ?string $useUp = null,
        public readonly ?string $notes = null,
    ) {}

    /** @return array<string, mixed> The answers as stored: the form's own keys, so from() reads them back. */
    public function toStored(): array
    {
        return [
            'people' => $this->people,
            'confidence' => $this->confidence->value,
            'budget' => $this->budget,
            'diets' => $this->diets,
            'avoid' => $this->avoid,
            'goals' => $this->goals,
            'equipment' => $this->equipment,
            'use_up' => $this->useUp,
            'notes' => $this->notes,
        ];
    }
}
