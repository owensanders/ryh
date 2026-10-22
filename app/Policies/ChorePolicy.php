<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class ChorePolicy
{
    use AuthorizesHouseholdAccessTrait;

    public function viewAny(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::cases());
    }

    public function create(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::managers());
    }

    public function update(User $user, Chore $chore): Response
    {
        return $this->inHouseholdAs($user, $chore->household_id, HouseholdRoleEnum::managers());
    }

    public function delete(User $user, Chore $chore): Response
    {
        return $this->update($user, $chore);
    }

    /** Owner and Adult can tick off any chore; Teen and Child only their own. */
    public function toggle(User $user, Chore $chore): Response
    {
        if ($chore->assigned_to === $user->id) {
            return $this->inHouseholdAs($user, $chore->household_id, HouseholdRoleEnum::cases());
        }

        return $this->inHouseholdAs($user, $chore->household_id, HouseholdRoleEnum::managers());
    }

    /** Sending everyone their outstanding-chores reminder. */
    public function notify(User $user, Household $household): Response
    {
        return $this->create($user, $household);
    }
}
