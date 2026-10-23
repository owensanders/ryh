<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Plans saved before the browser's data went camelCase get their meals' keys renamed, and back. */
class AiMealSuggestionKeysMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRenamesAStoredPlansMealKeysAndBack(): void
    {
        $user = User::factory()->create();
        $snake = [['name' => 'Fish pie', 'duration_label' => '45 min', 'estimated_cost' => 7.5, 'recipe_id' => 3, 'is_favourite' => true, 'tags' => []]];
        $camel = [['name' => 'Fish pie', 'durationLabel' => '45 min', 'estimatedCost' => 7.5, 'recipeId' => 3, 'isFavourite' => true, 'tags' => []]];

        DB::table('ai_meal_plans')->insert([
            'household_id' => $user->last_household_id,
            'status' => 'ready',
            'answers' => '{}',
            'meals' => json_encode($snake),
        ]);

        $migration = require database_path('migrations/2026_10_23_100000_store_ai_meal_suggestions_in_camel_case.php');
        assert($migration instanceof Migration && method_exists($migration, 'up') && method_exists($migration, 'down'));

        // MySQL's JSON columns sort an object's keys, so these compare by key, not order.
        $migration->up();
        $this->assertEquals($camel, $this->storedMeals());

        $migration->down();
        $this->assertEquals($snake, $this->storedMeals());
    }

    /** @return mixed The one plan's meals, decoded. */
    private function storedMeals(): mixed
    {
        return json_decode((string) DB::table('ai_meal_plans')->value('meals'), true);
    }
}
