<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\BudgetCategory;
use App\Models\Household;
use App\Models\User;
use App\Support\CurrentHouseholdHelper;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class BudgetCategoryPolicy
{
    use AuthorizesHouseholdAccessTrait;

    /** The budget screen: in the household in the URL, unless another is passed in. */
    public function viewAny(User $user, ?Household $household = null): Response
    {
        return $this->asMemberOf($user, $household ?? CurrentHouseholdHelper::household(), HouseholdRoleEnum::managers());
    }

    public function create(User $user): Response
    {
        return $this->as($user, HouseholdRoleEnum::managers());
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
