<?php

declare(strict_types=1);

namespace App\Results;

use App\Enums\InviteJoinOutcomeEnum;

/**
 * How joining from an invite link went, for the controller to choose where
 * to send them. A result, not a model: it can end several ways, and some
 * name a household that the person isn't in.
 */
final readonly class InviteJoinResult
{
    public function __construct(
        public InviteJoinOutcomeEnum $outcome,
        /** Null when the household has gone. */
        public ?string $householdName,
    ) {}
}
