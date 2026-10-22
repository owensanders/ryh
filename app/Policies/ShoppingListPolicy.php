<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\ShoppingList;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class ShoppingListPolicy
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

    public function update(User $user, ShoppingList $list): Response
    {
        return $this->inHouseholdAs($user, $list->household_id, HouseholdRoleEnum::managers());
    }

    public function delete(User $user, ShoppingList $list): Response
    {
        return $this->update($user, $list);
    }
}
