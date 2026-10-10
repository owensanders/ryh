<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Data\Input\PlannedMealInputData;
use App\Data\Input\RecipeDetailsInputData;
use App\Data\Input\RecipeInputData;
use App\Enums\MealSlotEnum;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\LaravelData\Optional;

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

    public function forDay(Household $household, CarbonImmutable $day, MealSlotEnum $slot): ?PlannedMeal
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

    public function create(Household $household, PlannedMealInputData $meal): PlannedMeal
    {
        return DB::transaction(function () use ($household, $meal): PlannedMeal {
            $created = $household->plannedMeals()->create([
                ...$this->mealColumns($meal),
                'recipe_id' => $this->resolveRecipeId($household, $meal),
            ]);

            return $created->refresh()->load(['recipe', 'cook']);
        });
    }

    public function update(PlannedMeal $meal, PlannedMealInputData $changes): PlannedMeal
    {
        return DB::transaction(function () use ($meal, $changes): PlannedMeal {
            $household = $meal->household ?? throw new LogicException('A planned meal always belongs to a household.');

            $meal->update([
                ...$this->mealColumns($changes),
                'recipe_id' => $this->resolveRecipeId($household, $changes),
            ]);

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

    public function createRecipe(Household $household, RecipeInputData $recipe): Recipe
    {
        return $household->recipes()->create($this->recipeColumns($recipe));
    }

    public function updateRecipe(Recipe $recipe, RecipeInputData $changes): Recipe
    {
        $recipe->update($this->recipeColumns($changes));

        return $recipe->refresh();
    }

    public function deleteRecipe(Recipe $recipe): void
    {
        $recipe->delete();
    }

    /** What a recipe planned straight from the meal form starts with, before any details sent with it. */
    private const array NEW_RECIPE_DEFAULTS = [
        'description' => null,
        'duration_label' => null,
        'difficulty' => null,
        'tags' => [],
        'ingredients' => [],
        'tint' => 0,
        'is_favourite' => false,
    ];

    /**
     * An existing recipe only has the details that were actually sent updated,
     * so planning it by id alone can't blank its description, tags, etc.
     */
    private function resolveRecipeId(Household $household, PlannedMealInputData $meal): int
    {
        $details = $this->sentRecipeDetails($meal->recipeDetails);

        if ($meal->recipeId !== null) {
            $recipe = $household->recipes()->findOrFail($meal->recipeId);

            if ($details !== []) {
                $recipe->update($details);
            }

            return $recipe->id;
        }

        return $household->recipes()->create([
            'name' => (string) $meal->newRecipeName,
            ...self::NEW_RECIPE_DEFAULTS,
            ...$details,
        ])->id;
    }

    /** @return array<string, mixed> Recipe columns for just the details the form sent. */
    private function sentRecipeDetails(RecipeDetailsInputData $details): array
    {
        return array_filter([
            'description' => $details->description,
            'duration_label' => $details->durationLabel,
            'difficulty' => $details->difficulty,
            'tags' => $details->tags,
            'ingredients' => $details->ingredients,
            'tint' => $details->tint,
            'is_favourite' => $details->isFavourite,
        ], fn (mixed $value) => ! $value instanceof Optional);
    }

    /** @return array<string, mixed> */
    private function mealColumns(PlannedMealInputData $meal): array
    {
        return [
            'planned_on' => $meal->plannedOn,
            'slot' => $meal->slot,
            'cook_id' => $meal->cookId,
        ];
    }

    /** @return array<string, mixed> */
    private function recipeColumns(RecipeInputData $recipe): array
    {
        return [
            'name' => $recipe->name,
            'description' => $recipe->description,
            'duration_label' => $recipe->durationLabel,
            'difficulty' => $recipe->difficulty,
            'tags' => $recipe->tags,
            'ingredients' => $recipe->ingredients,
            'tint' => $recipe->tint,
            'is_favourite' => $recipe->isFavourite,
        ];
    }
}
