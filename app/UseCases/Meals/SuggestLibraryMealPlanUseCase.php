<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Data\MealSuggestionData;
use App\Enums\MealDietEnum;
use App\Enums\RecipeTagEnum;
use App\Exceptions\MealPlanUnavailableException;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use Carbon\CarbonImmutable;

/**
 * Builds a week from the household's own recipe library - no AI call, so it
 * isn't limited. Only filters the library can actually answer are applied:
 * the avoid list (names and ingredients) and vegetarian/vegan via tags.
 */
class SuggestLibraryMealPlanUseCase
{
    // Matches the AI pool so regenerate and swaps behave the same.
    private const int POOL_SIZE = 14;

    private const int RECENT_DAYS = 14;

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
    ) {}

    /**
     * @param  array<int, string>  $diets
     *
     * @return array<int, MealSuggestionData>
     */
    public function execute(Household $household, array $diets, string $avoid): array
    {
        $avoidWords = collect(explode(',', $avoid))->map(fn (string $item) => mb_strtolower(trim($item)))->filter()->values();
        $today = CarbonImmutable::today();
        $recentRecipeIds = $this->meals->between($household, $today->subDays(self::RECENT_DAYS), $today->addDays(self::RECENT_DAYS))
            ->map(fn (PlannedMeal $meal) => $meal->recipe_id)
            ->unique();

        $eligible = $this->meals->allRecipes($household)
            ->filter(fn (Recipe $recipe) => $this->suitsDiets($recipe, $diets))
            ->reject(fn (Recipe $recipe) => $avoidWords->contains(fn (string $word) => $this->mentions($recipe, $word)));

        if ($eligible->isEmpty()) {
            throw MealPlanUnavailableException::noMatchingRecipes();
        }

        // Fresh recipes before recently planned ones, favourites first within
        // each group, shuffled so repeat requests don't give the same week.
        return $eligible
            ->shuffle()
            ->sortBy(fn (Recipe $recipe) => [$recentRecipeIds->contains($recipe->id) ? 1 : 0, $recipe->is_favourite ? 0 : 1])
            ->take(self::POOL_SIZE)
            ->map(MealSuggestionData::fromRecipe(...))
            ->values()
            ->all();
    }

    /** @param  array<int, string>  $diets */
    private function suitsDiets(Recipe $recipe, array $diets): bool
    {
        $tags = array_map(mb_strtolower(...), $recipe->tags ?? []);

        if (in_array(MealDietEnum::Vegan->value, $diets, true)) {
            return in_array(RecipeTagEnum::Vegan->value, $tags, true);
        }

        if (in_array(MealDietEnum::Vegetarian->value, $diets, true)) {
            return in_array(RecipeTagEnum::Vegetarian->value, $tags, true) || in_array(RecipeTagEnum::Vegan->value, $tags, true);
        }

        return true;
    }

    private function mentions(Recipe $recipe, string $word): bool
    {
        $text = mb_strtolower($recipe->name.' '.collect($recipe->ingredients ?? [])->pluck('name')->implode(' '));

        return str_contains($text, $word);
    }
}
