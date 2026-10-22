<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use App\Support\CurrentHouseholdHelper;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

/** Abilities over a household as a whole: the one in the URL, unless another is passed in. */
class HouseholdPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** The dashboard and the billing paywall: every member. */
    public function view(User $user, ?Household $household = null): Response
    {
        return $this->asMemberOf($user, $household ?? CurrentHouseholdHelper::household(), HouseholdRoleEnum::cases());
    }

    /** The House tab, invites, the join code, and billing: Owner only, never Adult. */
    public function manage(User $user, ?Household $household = null): Response
    {
        return $this->asMemberOf($user, $household ?? CurrentHouseholdHelper::household(), [HouseholdRoleEnum::Owner]);
    }
}
