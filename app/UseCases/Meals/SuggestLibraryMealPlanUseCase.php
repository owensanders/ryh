<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Enums\MealDiet;
use App\Enums\RecipeTag;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Builds a week from the household's own recipe library - no AI call, so it
 * isn't limited. Only filters the library can actually answer are applied:
 * the avoid list (names and ingredients) and vegetarian/vegan via tags.
 */
class SuggestLibraryMealPlanUseCase
{
    // Matches the AI pool so regenerate and swaps behave the same.
    private const POOL_SIZE = 14;

    private const RECENT_DAYS = 14;

    public function __construct(
        private readonly MealPlanRepositoryInterface $meals,
    ) {}

    /**
     * @param  array<int, string>  $diets
     * @return array<int, array{recipe_id: int, name: string, description: ?string, duration_label: ?string, difficulty: ?string, estimated_cost: null, tags: array<int, string>, ingredients: array<int, array{name: string, quantity: string|null}>, tint: int, is_favourite: bool}>
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
            throw new RuntimeException('None of your saved recipes match — try loosening the filters or add more recipes.');
        }

        // Fresh recipes before recently planned ones, favourites first within
        // each group, shuffled so repeat requests don't give the same week.
        return $eligible
            ->shuffle()
            ->sortBy(fn (Recipe $recipe) => [$recentRecipeIds->contains($recipe->id) ? 1 : 0, $recipe->is_favourite ? 0 : 1])
            ->take(self::POOL_SIZE)
            ->map(fn (Recipe $recipe) => [
                'recipe_id' => $recipe->id,
                'name' => $recipe->name,
                'description' => $recipe->description,
                'duration_label' => $recipe->duration_label,
                'difficulty' => $recipe->difficulty,
                'estimated_cost' => null,
                'tags' => $recipe->tags ?? [],
                'ingredients' => $recipe->ingredients ?? [],
                'tint' => $recipe->tint,
                'is_favourite' => $recipe->is_favourite,
            ])
            ->values()
            ->all();
    }

    /** @param  array<int, string>  $diets */
    private function suitsDiets(Recipe $recipe, array $diets): bool
    {
        $tags = array_map(mb_strtolower(...), $recipe->tags ?? []);

        if (in_array(MealDiet::Vegan->value, $diets, true)) {
            return in_array(RecipeTag::Vegan->value, $tags, true);
        }

        if (in_array(MealDiet::Vegetarian->value, $diets, true)) {
            return in_array(RecipeTag::Vegetarian->value, $tags, true) || in_array(RecipeTag::Vegan->value, $tags, true);
        }

        return true;
    }

    private function mentions(Recipe $recipe, string $word): bool
    {
        $text = mb_strtolower($recipe->name.' '.collect($recipe->ingredients ?? [])->pluck('name')->implode(' '));

        return str_contains($text, $word);
    }
}
