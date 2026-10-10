<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class AgendaGroupData extends Data
{
    /** @param array<int, AgendaEntryData> $items */
    public function __construct(
        public string $label,
        public array $items,
    ) {}
}
