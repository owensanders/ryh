<?php

declare(strict_types=1);

namespace App\Inputs;

use Spatie\LaravelData\Dto;

/** A new household as HouseholdSetupRequest validated it. */
final class HouseholdSetupInput extends Dto
{
    /** @param  list<InviteInput>  $invites */
    public function __construct(
        public readonly string $name,
        public readonly array $invites = [],
        /** The browser's; one PHP doesn't know falls back to the UK. */
        public readonly ?string $timezone = null,
    ) {}
}
