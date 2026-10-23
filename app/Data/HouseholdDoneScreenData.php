<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/** The screen after setting up a household, or after asking to join one. */
class HouseholdDoneScreenData extends Data
{
    public function __construct(
        #[LiteralTypeScriptType("'create' | 'join'")]
        public string $mode,
        public string $householdName,
        public int $inviteCount,
        /** They asked to join and are waiting to be let in. */
        public bool $pending,
    ) {}
}
