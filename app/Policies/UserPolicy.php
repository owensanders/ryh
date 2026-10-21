<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\User;
use App\Repositories\MembershipRepository;
use App\Support\CurrentHouseholdHelper;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use AuthorizesHouseholdAccessTrait;

    public function __construct(
        private readonly MembershipRepository $memberships,
    ) {}

    /** Changing a member's role, approving them, or removing them from the household. */
    public function manageMember(User $user, User $member): Response
    {
        $householdId = CurrentHouseholdHelper::id();
        $isMember = $householdId !== null && $this->memberships->belongsTo($member, $householdId);

        return $isMember
            ? $this->as($user, [HouseholdRoleEnum::Owner])
            : Response::denyAsNotFound();
    }
}
