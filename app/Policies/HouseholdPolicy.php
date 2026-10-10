<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

/** Abilities over the household the person is using, so they take no household argument. */
class HouseholdPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** The dashboard and the billing paywall: every member. */
    public function view(User $user): Response
    {
        return $this->as($user, HouseholdRoleEnum::cases());
    }

    /** The House tab, invites, the join code, and billing: Owner only, never Adult. */
    public function manage(User $user): Response
    {
        return $this->as($user, [HouseholdRoleEnum::Owner]);
    }
}
