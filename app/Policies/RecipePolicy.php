<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\User;
use App\Traits\AuthorizesHouseholdAccessTrait;
use Illuminate\Auth\Access\Response;

class RecipePolicy
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

    public function update(User $user, Recipe $recipe): Response
    {
        return $this->inHouseholdAs($user, $recipe->household_id, HouseholdRoleEnum::managers());
    }

    public function delete(User $user, Recipe $recipe): Response
    {
        return $this->update($user, $recipe);
    }

    public function addToShoppingList(User $user, Recipe $recipe): Response
    {
        return $this->update($user, $recipe);
    }
}
