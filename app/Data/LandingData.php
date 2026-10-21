<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** Where someone arriving without a household in the URL should go: set one up, pick one, or straight in. */
class LandingData extends Data
{
    public function __construct(
        public bool $needsSetup,
        public bool $needsChoice,
        public ?string $householdSlug,
    ) {}
}
