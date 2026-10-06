<?php

declare(strict_types=1);

namespace App\Ai\Fakes;

use App\Ai\Agents\MealPlanAgent;

/**
 * Stands in for the AI when the Playwright server runs (APP_ENV=e2e), so journeys
 * can walk the whole "Plan my week" flow without a paid call or a real key.
 */
class E2eMealPlan
{
    // Long enough for the waiting screen to tick past its first step (6s each).
    private const DELAY_SECONDS = 7;

    private const NAMES = [
        'Lemon chicken traybake', 'Air-fryer fish fingers', 'Bean chilli', 'Prawn laksa', 'Mushroom risotto',
        'Harissa lamb flatbreads', 'Teriyaki salmon', 'Veg-packed lasagne', 'Chickpea curry', 'Pork stir-fry',
        'Sausage and lentil stew', 'Halloumi fajitas', 'Tuna pasta bake', 'Sweet potato dhal',
    ];

    public static function register(): void
    {
        MealPlanAgent::fake(function () {
            sleep(self::DELAY_SECONDS);

            return ['meals' => self::meals()];
        });
    }

    /** @return list<array{name: string, description: string, duration_label: string, difficulty: string, estimated_cost: float, tags: list<string>, ingredients: list<array{name: string, quantity: string}>}> */
    public static function meals(): array
    {
        return array_map(fn (string $name) => [
            'name' => $name,
            'description' => 'A fixed e2e suggestion.',
            'duration_label' => '30 min',
            'difficulty' => 'Easy',
            'estimated_cost' => 8.5,
            'tags' => [],
            'ingredients' => [['name' => 'Onion', 'quantity' => '1']],
        ], self::NAMES);
    }
}
