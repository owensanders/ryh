<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SwitchCurrentHouseholdUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    public function execute(User $user, int $householdId): void
    {
        // The request validates this too; a household they aren't in doesn't exist for them.
        if (! $this->memberships->belongsTo($user, $householdId)) {
            throw (new ModelNotFoundException)->setModel(Household::class, [$householdId]);
        }

        $this->users->setLastHousehold($user, $householdId);
    }
}
