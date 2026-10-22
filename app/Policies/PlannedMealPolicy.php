<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class PlannedMealPolicy
{
    use AuthorizesHouseholdAccessTrait;

    public function viewAny(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::cases());
    }

    /** Planning a meal, or asking the AI or the recipe library for a week of them. */
    public function create(User $user, Household $household): Response
    {
        return $this->inHouseholdAs($user, $household->id, HouseholdRoleEnum::managers());
    }

    /** Editing or rescheduling. */
    public function update(User $user, PlannedMeal $meal): Response
    {
        return $this->inHouseholdAs($user, $meal->household_id, HouseholdRoleEnum::managers());
    }

    public function delete(User $user, PlannedMeal $meal): Response
    {
        return $this->update($user, $meal);
    }
}
