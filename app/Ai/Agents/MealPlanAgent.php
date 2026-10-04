<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Enums\CookingConfidence;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class MealPlanAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    private const POOL_SIZE = 12;

    /**
     * @param  list<string>  $diets
     * @param  list<string>  $avoid
     * @param  list<string>  $goals
     * @param  list<string>  $recentRecipeNames
     * @param  list<string>  $useUp
     */
    public function __construct(
        private readonly int $people,
        private readonly ?float $weeklyBudget,
        private readonly array $diets,
        private readonly array $avoid,
        private readonly array $goals,
        private readonly array $recentRecipeNames,
        private readonly CookingConfidence $confidence,
        private readonly array $useUp,
    ) {}

    public function maxTokens(): int
    {
        return (int) config('meal_planner.max_output_tokens');
    }

    public function instructions(): Stringable|string
    {
        $lines = [
            'You are a meal-planning assistant for a UK household. Suggest '.self::POOL_SIZE.' distinct dinner recipes.',
            "Cook for {$this->people} ".($this->people === 1 ? 'portion' : 'portions').' per recipe.',
            $this->confidence->instruction(),
            "Keep each name short (2-4 words) in sentence case, like 'Lemon chicken traybake'. Write durations like '25 min' or '1 hr 10', and difficulty as Easy, Medium or Hard.",
            'For each recipe, estimate the total ingredient cost in GBP at typical UK supermarket prices for the portions above.',
        ];

        if ($this->weeklyBudget !== null) {
            $lines[] = "Keep the whole pool affordable for a weekly food budget of roughly £{$this->weeklyBudget}: any week of dinners picked from it should fit.";
        }

        if ($this->useUp !== []) {
            $lines[] = 'The household wants to use these up first, so build some recipes around them where sensible: '.implode(', ', $this->useUp).'.';
        }

        if ($this->diets !== []) {
            $lines[] = 'Every recipe must suit these diets: '.implode(', ', $this->diets).'.';
        }

        if ($this->avoid !== []) {
            $lines[] = 'Avoid these ingredients entirely: '.implode(', ', $this->avoid).'.';
        }

        if ($this->goals !== []) {
            $lines[] = 'Favour recipes that support: '.implode(', ', $this->goals).'.';
        }

        if ($this->recentRecipeNames !== []) {
            $lines[] = "Don't repeat these recently planned meals: ".implode(', ', $this->recentRecipeNames).'.';
        }

        return implode(' ', $lines);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'meals' => $schema->array()
                ->items($schema->object([
                    'name' => $schema->string()->required(),
                    'description' => $schema->string()->required(),
                    'duration_label' => $schema->string()->required(),
                    'difficulty' => $schema->string()->required(),
                    'estimated_cost' => $schema->number()->min(0)->required(),
                    'tags' => $schema->array()->items($schema->string())->required(),
                    'ingredients' => $schema->array()->items($schema->object([
                        'name' => $schema->string()->required(),
                        'quantity' => $schema->string()->required(),
                    ]))->required(),
                ]))
                ->min(self::POOL_SIZE)
                ->max(self::POOL_SIZE)
                ->required(),
        ];
    }
}
