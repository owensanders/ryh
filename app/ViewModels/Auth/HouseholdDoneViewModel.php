<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Data\HouseholdDoneScreenData;
use App\Models\User;

class HouseholdDoneViewModel
{
    /**
     * `$setup` is what the setup or join step flashed. Without it (a reload,
     * or coming back later) the screen falls back on their last household.
     *
     * @param  array{mode: string, householdName: string, inviteCount: int}|null  $setup
     */
    public function toData(User $viewer, ?array $setup): HouseholdDoneScreenData
    {
        $pending = $viewer->lastMembership()?->pending === true;

        return new HouseholdDoneScreenData(
            mode: $setup['mode'] ?? ($pending ? 'join' : 'create'),
            householdName: $setup['householdName'] ?? $viewer->lastHousehold->name ?? '',
            inviteCount: $setup['inviteCount'] ?? 0,
            pending: $pending,
        );
    }
}
