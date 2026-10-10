<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class HouseholdOptionData extends Data
{
    /** @param  array<int, array{initials: string, colour: string}>  $memberAvatars */
    public function __construct(
        public int $id,
        public string $name,
        public string $role,
        public string $roleLabel,
        public int $memberCount,
        public array $memberAvatars,
    ) {}
}
