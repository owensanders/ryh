<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Exceptions\MembershipException;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;

class ChangeMemberRoleAction
{
    /**
     * Making someone a Teen or Child records `$changedBy` as confirming they're
     * that member's parent or guardian — the caller must have had them tick that box.
     *
     * @throws MembershipException
     */
    public function handle(User $member, Household $household, HouseholdRoleEnum $role, User $changedBy): void
    {
        $currentRole = $household->membershipOf($member)?->role;

        if ($currentRole === HouseholdRoleEnum::Owner && $role !== HouseholdRoleEnum::Owner && $household->isSoleOwner($member)) {
            throw MembershipException::soleOwnerCantStepDown();
        }

        $household->members()->updateExistingPivot($member->id, ['role' => $role, ...HouseholdUser::guardianAttributes($role, $changedBy->id)]);
    }
}
