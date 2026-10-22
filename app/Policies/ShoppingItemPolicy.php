<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\ShoppingItem;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class ShoppingItemPolicy
{
    use AuthorizesHouseholdAccessTrait;

    public function create(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::managers());
    }

    public function update(User $user, ShoppingItem $item): Response
    {
        return $this->inHouseholdAs($user, $this->householdOf($item), HouseholdRoleEnum::managers());
    }

    public function delete(User $user, ShoppingItem $item): Response
    {
        return $this->update($user, $item);
    }

    /** Items aren't assigned to anyone, so ticking off is down to role alone: Teen yes, Child no. */
    public function toggle(User $user, ShoppingItem $item): Response
    {
        return $this->inHouseholdAs($user, $this->householdOf($item), HouseholdRoleEnum::managersAndTeens());
    }

    private function householdOf(ShoppingItem $item): ?int
    {
        return $item->list?->household_id;
    }
}
