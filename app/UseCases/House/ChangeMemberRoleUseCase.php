<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ChangeMemberRoleUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /**
     * Making someone a Teen or Child records `$changedBy` as confirming they're
     * that member's parent or guardian — the caller must have had them tick that box.
     *
     * @throws ValidationException
     */
    public function execute(User $member, Household $household, HouseholdRoleEnum $role, User $changedBy): User
    {
        $currentRole = $member->households()->where('households.id', $household->id)->first()?->pivot->role;

        if ($currentRole === HouseholdRoleEnum::Owner && $role !== HouseholdRoleEnum::Owner && $this->households->isSoleOwner($household, $member)) {
            throw ValidationException::withMessages(['role' => "You're the only owner — promote someone else first."]);
        }

        $member->households()->updateExistingPivot($household->id, [
            'role' => $role,
            ...HouseholdUser::guardianAttributes($role, $changedBy->id),
        ]);

        return $member->refresh();
    }
}
