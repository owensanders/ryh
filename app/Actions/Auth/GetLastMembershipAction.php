<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\HouseholdUser;
use App\Models\User;

/** The setup screens' question: did they just join somewhere they're still waiting to be let into? */
class GetLastMembershipAction
{
    public function handle(User $user): ?HouseholdUser
    {
        return $user->lastMembership();
    }
}
