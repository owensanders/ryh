<?php

declare(strict_types=1);

namespace App\Data\Input;

/** A recipe as the library form submits it, validated by RecipeRequest. */
final readonly class RecipeInputData
{
    /**
     * @param  list<string>  $tags  RecipeTagEnum values.
     * @param  list<array{name: string, quantity: string|null}>  $ingredients
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $durationLabel,
        public ?string $difficulty,
        public array $tags,
        public array $ingredients,
        public int $tint,
        public bool $isFavourite,
    ) {}
}
