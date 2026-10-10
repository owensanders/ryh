<?php

declare(strict_types=1);

namespace App\Data\Input;

use Spatie\LaravelData\Optional;

/**
 * Recipe details sent alongside a planned meal. Each is Optional when the
 * form didn't send it, so planning an existing recipe by id alone leaves its
 * description, tags, etc. untouched rather than blanking them.
 */
final readonly class RecipeDetailsInputData
{
    /**
     * @param  list<string>|Optional  $tags
     * @param  list<array{name: string, quantity: string|null}>|Optional  $ingredients
     */
    public function __construct(
        public string|Optional|null $description,
        public string|Optional|null $durationLabel,
        public string|Optional|null $difficulty,
        public array|Optional $tags,
        public array|Optional $ingredients,
        public int|Optional $tint,
        public bool|Optional $isFavourite,
    ) {}
}
