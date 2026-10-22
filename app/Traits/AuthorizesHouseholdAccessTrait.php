<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\HouseholdRoleEnum;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * The question every policy asks: what is this person's role in the household
 * the record (or household) belongs to, and does it allow this? Answered from
 * the record and the user alone, never the request, so a check gives the same
 * answer in a queued job as on a page. That the record belongs to the
 * household in the URL is settled earlier, when the route binds it.
 */
trait AuthorizesHouseholdAccessTrait
{
    /**
     * Not a member there, or still pending, is a 404, so a record's existence
     * is never confirmed; a role that can't do this is a 403.
     *
     * @param  list<HouseholdRoleEnum>  $roles
     */
    protected function inHouseholdAs(User $user, ?int $householdId, array $roles): Response
    {
        $membership = $householdId === null ? null : $user->membershipIn($householdId);

        if ($membership === null || $membership->pending) {
            return Response::denyAsNotFound();
        }

        return in_array($membership->role, $roles, true)
            ? Response::allow()
            : Response::deny();
    }
}
