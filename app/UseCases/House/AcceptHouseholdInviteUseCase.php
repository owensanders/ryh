<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcceptHouseholdInviteUseCase
{
    public function __construct(
        private readonly ApproveMemberUseCase $approveMember,
        private readonly MembershipRepositoryInterface $memberships,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /** @return string The household's name. */
    public function execute(User $user, int $householdId): string
    {
        $household = $this->households->find($householdId);

        if ($household === null) {
            throw (new ModelNotFoundException)->setModel(Household::class, [$householdId]);
        }

        $pivot = $this->memberships->find($user, $household);

        if ($pivot === null || $pivot->pending !== true) {
            throw (new ModelNotFoundException)->setModel(Household::class, [$householdId]);
        }

        $this->approveMember->execute($user, $household);

        return $household->name;
    }
}
