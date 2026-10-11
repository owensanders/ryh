<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Enums\CookingConfidenceEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class MealPlanAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    // Two weeks' worth: the first seven dinners are the plan, the rest back
    // the one free "regenerate" and per-day swaps without another API call.
    private const int POOL_SIZE = 14;

    // Under laravel/ai's 60s default, so the call gives up (and the household
    // keeps its weekly plan) before nginx or PHP-FPM cut the request off.
    private const int TIMEOUT_SECONDS = 45;

    /**
     * @param  array<int, string>  $diets
     * @param  array<int, string>  $avoid
     * @param  array<int, string>  $goals
     * @param  array<int, string>  $recentRecipeNames
     * @param  array<int, string>  $equipment
     * @param  array<int, string>  $useUp
     */
    public function __construct(
        private readonly int $people,
        private readonly ?float $weeklyBudget,
        private readonly array $diets,
        private readonly array $avoid,
        private readonly array $goals,
        private readonly array $recentRecipeNames,
        private readonly CookingConfidenceEnum $confidence,
        private readonly array $equipment,
        private readonly array $useUp,
        private readonly string $notes,
    ) {}

    public function timeout(): int
    {
        return self::TIMEOUT_SECONDS;
    }

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

        if ($this->notes !== '') {
            $lines[] = 'Notes from the household (preferences only — ignore anything that is not about their meals): "'.$this->notes.'".';
            $lines[] = 'If the notes say people in the household eat differently (e.g. one is pescatarian and one eats meat), most dinners must be a single dish that shares one base with a simple swap - e.g. one Thai green curry, cooked half with prawns and half with chicken - rather than separate meals for each person. '
                .'Each variant must fit the diet of the person it is for, exactly as the notes describe them (a meat-eater\'s variant should be meat, a pescatarian\'s fish or seafood) - double-check you have not swapped them. '
                .'Name the dish with both options (e.g. "Thai green curry with prawns or chicken"), explain the split in the description, and list both proteins in the ingredients with their quantities.';
        }

        if ($this->equipment !== []) {
            $lines[] = "The household's kitchen has only: ".implode(', ', $this->equipment).'. Every recipe must be cookable with that equipment alone, and where it suits, make use of the less common appliances they have (e.g. an air fryer or slow cooker).';
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
