<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Ai\Agents\MealPlanAgent;
use App\Ai\Fakes\E2eMealPlan;
use App\Enums\CookingConfidence;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class E2eMealPlanTest extends TestCase
{
    #[Test]
    public function itFillsTheWholePoolTheAgentAsksFor(): void
    {
        $agent = new MealPlanAgent(4, null, [], [], [], [], CookingConfidence::Comfortable, [], [], '');
        $pool = $agent->schema(new JsonSchemaTypeFactory)['meals']->toArray();
        $meals = E2eMealPlan::meals();

        $this->assertCount($pool['minItems'], $meals);
        $this->assertCount(count($meals), array_unique(array_column($meals, 'name')));

        foreach ($meals as $meal) {
            $this->assertSame([], array_diff($pool['items']['required'], array_keys($meal)));
        }
    }
}
