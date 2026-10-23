<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A plan's meals are stored as MealSuggestionData::toArray() writes them,
 * which is now camelCase; plans saved before have snake_case keys. This
 * renames those keys, so AiMealPlanData can read them back.
 */
return new class extends Migration {
    /** The snake_case key => its camelCase name. */
    private const array KEYS = [
        'duration_label' => 'durationLabel',
        'estimated_cost' => 'estimatedCost',
        'recipe_id' => 'recipeId',
        'is_favourite' => 'isFavourite',
    ];

    public function up(): void
    {
        $this->rename(self::KEYS);
    }

    public function down(): void
    {
        $this->rename(array_flip(self::KEYS));
    }

    /** @param  array<string, string>  $keys */
    private function rename(array $keys): void
    {
        DB::table('ai_meal_plans')->whereNotNull('meals')->select(['id', 'meals'])->orderBy('id')
            ->each(function (object $plan) use ($keys): void {
                /** @var list<array<string, mixed>> $meals */
                $meals = json_decode($plan->meals, true, flags: JSON_THROW_ON_ERROR);

                $renamed = array_map(function (array $meal) use ($keys): array {
                    $result = [];

                    foreach ($meal as $key => $value) {
                        $result[$keys[$key] ?? $key] = $value;
                    }

                    return $result;
                }, $meals);

                DB::table('ai_meal_plans')->where('id', $plan->id)->update(['meals' => json_encode($renamed, JSON_THROW_ON_ERROR)]);
            });
    }
};
