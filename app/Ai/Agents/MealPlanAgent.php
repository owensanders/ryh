<?php

declare(strict_types=1);

namespace App\Ai\Agents;

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
     */
    public function __construct(
        private readonly int $people,
        private readonly ?float $weeklyBudget,
        private readonly array $diets,
        private readonly array $avoid,
        private readonly array $goals,
        private readonly array $recentRecipeNames,
    ) {}

    public function instructions(): Stringable|string
    {
        $lines = [
            'You are a meal-planning assistant for a UK household. Suggest '.self::POOL_SIZE.' distinct dinner recipes.',
            "Cook for {$this->people} ".($this->people === 1 ? 'portion' : 'portions').' per recipe.',
        ];

        if ($this->weeklyBudget !== null) {
            $lines[] = "Keep the whole pool affordable for a weekly food budget of roughly £{$this->weeklyBudget}.";
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
