<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** Changing a member's role, approving them, or removing them from the household. */
    public function manageMember(User $user, User $member): Response
    {
        $isMember = $member->households()->where('households.id', $user->current_household_id)->exists();

        return $isMember
            ? $this->as($user, [HouseholdRoleEnum::Owner])
            : Response::denyAsNotFound();
    }
}
