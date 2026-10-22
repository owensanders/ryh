<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class BudgetCategoryPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** The budget screen. */
    public function viewAny(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::managers());
    }

    public function create(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::managers());
    }

    public function update(User $user, BudgetCategory $category): Response
    {
        return $this->inHouseholdAs($user, $category->household_id, HouseholdRoleEnum::managers());
    }

    public function delete(User $user, BudgetCategory $category): Response
    {
        return $this->update($user, $category);
    }
}
