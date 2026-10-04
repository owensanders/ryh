<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Ai\Agents\MealPlanAgent;
use App\Enums\CookingConfidence;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MealPlanAgentTest extends TestCase
{
    #[Test]
    public function itTailorsInstructionsToConfidenceAndIngredientsToUseUp(): void
    {
        $agent = $this->agent(CookingConfidence::Beginner, ['chickpeas', 'spinach']);

        $instructions = (string) $agent->instructions();

        $this->assertStringContainsString(CookingConfidence::Beginner->instruction(), $instructions);
        $this->assertStringContainsString('use these up first', $instructions);
        $this->assertStringContainsString('chickpeas, spinach', $instructions);
        $this->assertStringContainsString('£85', $instructions);
        $this->assertStringContainsString('sentence case', $instructions);
    }

    #[Test]
    public function itLeavesOutTheUseUpLineWhenThereIsNothingToUseUp(): void
    {
        $this->assertStringNotContainsString('use these up first', (string) $this->agent(CookingConfidence::Comfortable, [])->instructions());
    }

    #[Test]
    public function itAsksForAnEstimatedCostPerRecipe(): void
    {
        $schema = $this->agent(CookingConfidence::Comfortable, [])->schema(new JsonSchemaTypeFactory);

        $recipe = $schema['meals']->toArray()['items']['properties'];

        $this->assertArrayHasKey('estimated_cost', $recipe);
        $this->assertSame('number', $recipe['estimated_cost']['type']);
    }

    #[Test]
    public function itCapsOutputTokensFromConfig(): void
    {
        config(['meal_planner.max_output_tokens' => 1234]);

        $this->assertSame(1234, $this->agent(CookingConfidence::Comfortable, [])->maxTokens());
    }

    /** @param  list<string>  $useUp */
    private function agent(CookingConfidence $confidence, array $useUp): MealPlanAgent
    {
        return new MealPlanAgent(4, 85.0, [], [], [], [], $confidence, $useUp);
    }
}
