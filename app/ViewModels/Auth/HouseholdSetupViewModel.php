<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Data\HouseholdSetupScreenData;
use App\Models\User;

class HouseholdSetupViewModel
{
    public function toData(User $viewer, ?string $fullHousehold, ?string $reinviteHousehold): HouseholdSetupScreenData
    {
        return new HouseholdSetupScreenData(
            trialAvailable: $viewer->trial_claimed_at === null,
            fullHousehold: $fullHousehold,
            reinviteHousehold: $reinviteHousehold,
            maxMembers: (int) config('plans.max_members'),
        );
    }
}
