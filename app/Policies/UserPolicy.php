<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use AuthorizesHouseholdAccessTrait;

    public function __construct(
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    /** Changing a member's role, approving them, or removing them from the household. */
    public function manageMember(User $user, User $member): Response
    {
        $isMember = $user->current_household_id !== null && $this->memberships->belongsTo($member, $user->current_household_id);

        return $isMember
            ? $this->as($user, [HouseholdRoleEnum::Owner])
            : Response::denyAsNotFound();
    }
}
