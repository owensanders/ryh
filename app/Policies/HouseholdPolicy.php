<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

/** Abilities over a household as a whole. */
class HouseholdPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** The dashboard and the billing paywall: every member. */
    public function view(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::cases());
    }

    /** The House tab, invites, the join code, and billing: Owner only, never Adult. */
    public function manage(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, [HouseholdRoleEnum::Owner]);
    }

    /** Changing a member's role, approving them (so pending counts), or removing them. */
    public function manageMember(User $user, Household $household, User $member): Response
    {
        if ($member->membershipIn($household->id) === null) {
            return Response::denyAsNotFound();
        }

        return $this->manage($user, $household);
    }
}
