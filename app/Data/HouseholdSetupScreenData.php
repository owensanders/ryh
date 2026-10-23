<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** The screen where someone without a household creates one or joins with a code. */
class HouseholdSetupScreenData extends Data
{
    public function __construct(
        /** The free trial is once per person: a second household starts without one. */
        public bool $trialAvailable,
        /** The household an invite link couldn't add them to because it had filled up. */
        public ?string $fullHousehold,
        /** The household whose Teen or Child invite link predates guardian confirmation, so it couldn't add them. */
        public ?string $reinviteHousehold,
        public int $maxMembers,
    ) {}
}
