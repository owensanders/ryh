<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Models\Household;
use App\Models\User;
use App\Repositories\HouseholdRepository;
use App\Repositories\MembershipRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcceptHouseholdInviteUseCase
{
    public function __construct(
        private readonly ApproveMemberUseCase $approveMember,
        private readonly MembershipRepository $memberships,
        private readonly HouseholdRepository $households,
    ) {}

    /** @return string The household's name. */
    public function execute(User $user, int $householdId): string
    {
        $household = $this->households->find($householdId);

        if ($household === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $pivot = $this->memberships->find($user, $household);

        if ($pivot === null || $pivot->pending !== true) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $this->approveMember->execute($user, $household);

        return $household->name;
    }
}
