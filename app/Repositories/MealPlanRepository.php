<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Enums\MealSlot;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class MealPlanRepository implements MealPlanRepositoryInterface
{
    /** @return Collection<int, PlannedMeal> */
    public function between(Household $household, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $household->plannedMeals()
            ->with(['recipe', 'cook'])
            ->whereBetween('planned_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('planned_on')
            ->orderBy('id')
            ->get();
    }

    public function forDay(Household $household, CarbonImmutable $day, MealSlot $slot): ?PlannedMeal
    {
        return $household->plannedMeals()
            ->with(['recipe', 'cook'])
            ->whereDate('planned_on', $day->toDateString())
            ->where('slot', $slot)
            ->first();
    }

    public function reschedule(PlannedMeal $meal, CarbonImmutable $day): PlannedMeal
    {
        $meal->update(['planned_on' => $day->toDateString()]);

        return $meal->refresh();
    }

    private const NEW_RECIPE_KEYS = [
        'new_recipe_name', 'new_recipe_description', 'new_recipe_duration_label',
        'new_recipe_difficulty', 'new_recipe_tags', 'new_recipe_ingredients',
        'new_recipe_tint', 'new_recipe_is_favourite', 'new_recipe_shopping_list_id',
    ];

    /** @param  array<string, mixed>  $attributes */
    public function create(Household $household, array $attributes): PlannedMeal
    {
        return DB::transaction(function () use ($household, $attributes) {
            $attributes['recipe_id'] = $this->resolveRecipeId($household, $attributes);
            $attributes = array_diff_key($attributes, array_flip(self::NEW_RECIPE_KEYS));

            $meal = $household->plannedMeals()->create($attributes);

            return $meal->refresh()->load(['recipe', 'cook']);
        });
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(PlannedMeal $meal, array $attributes): PlannedMeal
    {
        return DB::transaction(function () use ($meal, $attributes) {
            $household = $meal->household ?? throw new LogicException('A planned meal always belongs to a household.');
            $attributes['recipe_id'] = $this->resolveRecipeId($household, $attributes);
            $attributes = array_diff_key($attributes, array_flip(self::NEW_RECIPE_KEYS));

            $meal->update($attributes);

            return $meal->refresh()->load(['recipe', 'cook']);
        });
    }

    public function delete(PlannedMeal $meal): void
    {
        $meal->delete();
    }

    public function unassignCook(Household $household, User $cook): void
    {
        PlannedMeal::where('household_id', $household->id)->where('cook_id', $cook->id)->update(['cook_id' => null]);
    }

    /** @return Collection<int, Recipe> */
    public function favouriteRecipes(Household $household, int $limit): Collection
    {
        return $household->recipes()
            ->where('is_favourite', true)
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function countRecipes(Household $household): int
    {
        return $household->recipes()->count();
    }

    /** @return Collection<int, Recipe> */
    public function allRecipes(Household $household): Collection
    {
        return $household->recipes()
            ->orderByDesc('is_favourite')
            ->orderBy('name')
            ->get();
    }

    /** @param  array<string, mixed>  $attributes */
    public function createRecipe(Household $household, array $attributes): Recipe
    {
        return $household->recipes()->create($attributes);
    }

    /** @param  array<string, mixed>  $attributes */
    public function updateRecipe(Recipe $recipe, array $attributes): Recipe
    {
        $recipe->update($attributes);

        return $recipe->refresh();
    }

    public function deleteRecipe(Recipe $recipe): void
    {
        $recipe->delete();
    }

    /** Recipe column => planned-meal attribute carrying it, with its default for a new recipe. */
    private const RECIPE_DETAILS = [
        'description' => ['new_recipe_description', null],
        'duration_label' => ['new_recipe_duration_label', null],
        'difficulty' => ['new_recipe_difficulty', null],
        'tags' => ['new_recipe_tags', []],
        'ingredients' => ['new_recipe_ingredients', []],
        'tint' => ['new_recipe_tint', 0],
        'is_favourite' => ['new_recipe_is_favourite', false],
    ];

    /**
     * An existing recipe only has the details that were actually sent updated,
     * so planning it by id alone can't blank its description, tags, etc.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function resolveRecipeId(Household $household, array $attributes): int
    {
        $provided = [];

        foreach (self::RECIPE_DETAILS as $column => [$key]) {
            if (array_key_exists($key, $attributes)) {
                $provided[$column] = $attributes[$key];
            }
        }

        if (! empty($attributes['recipe_id'])) {
            $recipe = $household->recipes()->findOrFail((int) $attributes['recipe_id']);

            if ($provided !== []) {
                $this->updateRecipe($recipe, $provided);
            }

            return $recipe->id;
        }

        $defaults = array_map(fn (array $detail) => $detail[1], self::RECIPE_DETAILS);

        return $this->createRecipe($household, [
            'name' => trim((string) $attributes['new_recipe_name']),
            ...$defaults,
            ...$provided,
        ])->id;
    }
}
