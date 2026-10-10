<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Models\User;

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

        abort_if($household === null, 404);

        $pivot = $this->memberships->find($user, $household);

        abort_if($pivot === null || $pivot->pending !== true, 404);

        $this->approveMember->execute($user, $household);

        return $household->name;
    }
}
