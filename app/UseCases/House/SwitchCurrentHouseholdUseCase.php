<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

class SwitchCurrentHouseholdUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    public function execute(User $user, int $householdId): void
    {
        abort_unless($this->memberships->belongsTo($user, $householdId), 403);

        $this->users->setCurrentHousehold($user, $householdId);
    }
}
