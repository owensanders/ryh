<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Dto;

/** What a week planned from the recipe library should steer around, as SuggestLibraryMealPlanRequest validated it. */
final class LibraryMealPlanInput extends Dto
{
    /** @param  list<string>  $diets  MealDietEnum values. */
    public function __construct(
        public readonly array $diets = [],
        /** Ingredients to leave out, comma separated. */
        public readonly ?string $avoid = null,
    ) {}
}
