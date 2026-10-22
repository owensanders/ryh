<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use App\Support\CurrentHouseholdHelper;
use Illuminate\Auth\Access\Response;

/**
 * The two questions every policy asks: is this record in the household this
 * request is for (its /h/{household} URL), and does their role there allow this?
 */
trait AuthorizesHouseholdAccessTrait
{
    /**
     * A record from another household (or none) is a 404, so its existence
     * is never confirmed; a role that can't do this is a 403.
     *
     * @param  list<HouseholdRoleEnum>  $roles
     */
    protected function inHouseholdAs(User $user, ?int $householdId, array $roles): Response
    {
        if ($householdId === null || $householdId !== CurrentHouseholdHelper::id()) {
            return Response::denyAsNotFound();
        }

        return $this->as($user, $roles);
    }

    /** @param  list<HouseholdRoleEnum>  $roles */
    protected function as(User $user, array $roles): Response
    {
        return in_array(CurrentHouseholdHelper::role(), $roles, true)
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Their role in a household passed in, not only the one in the URL: the
     * nav on the settings pages asks about the household they were last in.
     * Not a member, or still pending, is a 404 like any other household's record.
     *
     * @param  list<HouseholdRoleEnum>  $roles
     */
    protected function asMemberOf(User $user, Household $household, array $roles): Response
    {
        if ($household->id === CurrentHouseholdHelper::id()) {
            return $this->as($user, $roles);
        }

        $membership = $household->membershipOf($user);

        if ($membership === null || $membership->pending) {
            return Response::denyAsNotFound();
        }

        return in_array($membership->role, $roles, true)
            ? Response::allow()
            : Response::deny();
    }
}
