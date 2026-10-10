<?php

declare(strict_types=1);

namespace App\Enums;

/** What came of joining a household from an invite link at registration. */
enum InviteJoinOutcomeEnum: string
{
    case Joined = 'joined';

    /** The household was deleted after the invite went out. */
    case HouseholdGone = 'household_gone';

    /** A Teen or Child invite sent before guardians had to confirm, so it can't be used. */
    case NeedsGuardian = 'needs_guardian';

    case HouseholdFull = 'household_full';
}
