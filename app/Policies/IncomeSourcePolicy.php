<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\IncomeSource;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class IncomeSourcePolicy
{
    use AuthorizesHouseholdAccessTrait;

    public function create(User $user): Response
    {
        return $this->as($user, HouseholdRoleEnum::managers());
    }

    public function update(User $user, IncomeSource $income): Response
    {
        return $this->inHouseholdAs($user, $income->household_id, HouseholdRoleEnum::managers());
    }

    public function delete(User $user, IncomeSource $income): Response
    {
        return $this->update($user, $income);
    }
}
