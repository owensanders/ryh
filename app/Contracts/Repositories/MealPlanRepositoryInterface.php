<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\Input\PlannedMealInputData;
use App\Data\Input\RecipeInputData;
use App\Enums\MealSlotEnum;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface MealPlanRepositoryInterface
{
    /** @return Collection<int, PlannedMeal> */
    public function between(Household $household, CarbonImmutable $from, CarbonImmutable $to): Collection;

    public function forDay(Household $household, CarbonImmutable $day, MealSlotEnum $slot): ?PlannedMeal;

    public function reschedule(PlannedMeal $meal, CarbonImmutable $day): PlannedMeal;

    public function create(Household $household, PlannedMealInputData $meal): PlannedMeal;

    public function update(PlannedMeal $meal, PlannedMealInputData $changes): PlannedMeal;

    public function delete(PlannedMeal $meal): void;

    /** Clears this member as cook on this household's planned meals, leaving the meals in place. */
    public function unassignCook(Household $household, User $cook): void;

    /** @return Collection<int, Recipe> */
    public function favouriteRecipes(Household $household, int $limit): Collection;

    public function countRecipes(Household $household): int;

    /** @return Collection<int, Recipe> */
    public function allRecipes(Household $household): Collection;

    public function createRecipe(Household $household, RecipeInputData $recipe): Recipe;

    public function updateRecipe(Recipe $recipe, RecipeInputData $changes): Recipe;

    public function deleteRecipe(Recipe $recipe): void;
}
