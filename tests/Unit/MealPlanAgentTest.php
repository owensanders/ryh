<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Ai\Agents\MealPlanAgent;
use App\Enums\CookingConfidenceEnum;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MealPlanAgentTest extends TestCase
{
    #[Test]
    public function itTailorsInstructionsToConfidenceAndIngredientsToUseUp(): void
    {
        $agent = $this->agent(CookingConfidenceEnum::Beginner, ['chickpeas', 'spinach']);

        $instructions = (string) $agent->instructions();

        $this->assertStringContainsString(CookingConfidenceEnum::Beginner->instruction(), $instructions);
        $this->assertStringContainsString('use these up first', $instructions);
        $this->assertStringContainsString('chickpeas, spinach', $instructions);
        $this->assertStringContainsString('£85', $instructions);
        $this->assertStringContainsString('sentence case', $instructions);
    }

    #[Test]
    public function itLeavesOutTheUseUpLineWhenThereIsNothingToUseUp(): void
    {
        $this->assertStringNotContainsString('use these up first', (string) $this->agent(CookingConfidenceEnum::Comfortable, [])->instructions());
    }

    #[Test]
    public function itAsksForAnEstimatedCostPerRecipe(): void
    {
        $schema = $this->agent(CookingConfidenceEnum::Comfortable, [])->schema(new JsonSchemaTypeFactory());

        $recipe = $schema['meals']->toArray()['items']['properties'];

        $this->assertArrayHasKey('estimated_cost', $recipe);
        $this->assertSame('number', $recipe['estimated_cost']['type']);
    }

    #[Test]
    public function itAsksForTwoWeeksOfDinnersSoRegenerateAndSwapsNeedNoExtraCall(): void
    {
        $agent = $this->agent(CookingConfidenceEnum::Comfortable, []);

        $this->assertStringContainsString('Suggest 14 distinct dinner recipes', (string) $agent->instructions());

        $meals = $agent->schema(new JsonSchemaTypeFactory())['meals']->toArray();
        $this->assertSame(14, $meals['minItems']);
        $this->assertSame(14, $meals['maxItems']);
    }

    #[Test]
    public function itAsksForSharedBaseDinnersWhenNotesDescribeMixedDiets(): void
    {
        $instructions = (string) $this->agent(CookingConfidenceEnum::Comfortable, [], 'My wife is pescatarian but I eat meat')->instructions();

        $this->assertStringContainsString('"My wife is pescatarian but I eat meat"', $instructions);
        $this->assertStringContainsString('shares one base with a simple swap', $instructions);
    }

    #[Test]
    public function itLeavesNotesOutWhenThereAreNone(): void
    {
        $this->assertStringNotContainsString('Notes from the household', (string) $this->agent(CookingConfidenceEnum::Comfortable, [])->instructions());
    }

    #[Test]
    public function itCapsOutputTokensFromConfig(): void
    {
        config(['meal_planner.max_output_tokens' => 1234]);

        $this->assertSame(1234, $this->agent(CookingConfidenceEnum::Comfortable, [])->maxTokens());
    }

    #[Test]
    public function itLimitsRecipesToTheHouseholdsKitchenEquipment(): void
    {
        $instructions = (string) $this->agent(CookingConfidenceEnum::Comfortable, [], equipment: ['Hob', 'Air fryer'])->instructions();

        $this->assertStringContainsString("The household's kitchen has only: Hob, Air fryer.", $instructions);
    }

    #[Test]
    public function itLeavesEquipmentOutWhenNoneIsGiven(): void
    {
        $this->assertStringNotContainsString('kitchen has only', (string) $this->agent(CookingConfidenceEnum::Comfortable, [])->instructions());
    }

    /**
     * @param  list<string>  $useUp
     * @param  list<string>  $equipment
     */
    private function agent(CookingConfidenceEnum $confidence, array $useUp, string $notes = '', array $equipment = []): MealPlanAgent
    {
        return new MealPlanAgent(4, 85.0, [], [], [], [], $confidence, $equipment, $useUp, $notes);
    }
}
