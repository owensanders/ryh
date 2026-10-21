<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use App\Repositories\MembershipRepository;
use Illuminate\Validation\ValidationException;

class ChangeMemberRoleUseCase
{
    public function __construct(
        private readonly MembershipRepository $memberships,
    ) {}

    /**
     * Making someone a Teen or Child records `$changedBy` as confirming they're
     * that member's parent or guardian — the caller must have had them tick that box.
     *
     * @throws ValidationException
     */
    public function execute(User $member, Household $household, HouseholdRoleEnum $role, User $changedBy): void
    {
        $currentRole = $this->memberships->find($member, $household)?->role;

        if ($currentRole === HouseholdRoleEnum::Owner && $role !== HouseholdRoleEnum::Owner && $this->memberships->isSoleOwner($household, $member)) {
            throw ValidationException::withMessages(['role' => "You're the only owner — promote someone else first."]);
        }

        $this->memberships->changeRole($member, $household, $role, $changedBy->id);
    }
}
