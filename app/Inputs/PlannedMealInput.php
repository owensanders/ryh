<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Enums\MealSlotEnum;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/**
 * A planned meal as PlannedMealRequest validated it: a saved recipe by id, or
 * a new one by name. The newRecipe* details are Optional when the form didn't
 * send them, so planning a saved recipe by id alone leaves its details as
 * they are (ResolvePlannedRecipeAction).
 */
#[MapInputName(SnakeCaseMapper::class)]
final class PlannedMealInput extends Dto
{
    /**
     * @param  list<string>|Optional  $newRecipeTags  RecipeTagEnum values.
     * @param  list<array{name: string, quantity: string|null}>|Optional  $newRecipeIngredients
     */
    public function __construct(
        #[WithCast(DateTimeInterfaceCast::class, format: '!Y-m-d')]
        public readonly CarbonImmutable $plannedOn,
        public readonly MealSlotEnum $slot,
        public readonly ?int $recipeId = null,
        public readonly ?string $newRecipeName = null,
        public readonly string|Optional|null $newRecipeDescription = new Optional(),
        public readonly string|Optional|null $newRecipeDurationLabel = new Optional(),
        public readonly string|Optional|null $newRecipeDifficulty = new Optional(),
        public readonly array|Optional $newRecipeTags = new Optional(),
        public readonly array|Optional $newRecipeIngredients = new Optional(),
        public readonly int|Optional|null $newRecipeTint = new Optional(),
        public readonly bool|Optional $newRecipeIsFavourite = new Optional(),
        /** A list to send the recipe's ingredients to, if one was picked. */
        public readonly ?int $newRecipeShoppingListId = null,
        public readonly ?int $cookId = null,
    ) {}
}
