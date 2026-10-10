<?php

declare(strict_types=1);

namespace App\Data\Input;

/** A new household from the setup wizard, validated by HouseholdSetupRequest. */
final readonly class HouseholdSetupInputData
{
    /** @param  list<InviteInputData>  $invites */
    public function __construct(
        public string $name,
        public array $invites,
    ) {}
}
