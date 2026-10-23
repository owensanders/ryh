<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** A recipe as RecipeRequest validated it. */
#[MapInputName(SnakeCaseMapper::class)]
final class RecipeInput extends Dto
{
    /**
     * @param  list<string>  $tags  RecipeTagEnum values.
     * @param  list<array{name: string, quantity: string|null}>  $ingredients
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $durationLabel = null,
        public readonly ?string $difficulty = null,
        public readonly array $tags = [],
        public readonly array $ingredients = [],
        public readonly int $tint = 0,
        public readonly bool $isFavourite = false,
        /** A list to send the ingredients to, if one was picked. */
        public readonly ?int $shoppingListId = null,
    ) {}
}
