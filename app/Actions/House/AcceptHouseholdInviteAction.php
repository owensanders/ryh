<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Models\User;
use App\Repositories\HouseholdRepository;
use App\Repositories\MembershipRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcceptHouseholdInviteAction
{
    public function __construct(
        private readonly ApproveMemberAction $approveMember,
        private readonly MembershipRepository $memberships,
        private readonly HouseholdRepository $households,
    ) {}

    /** @return string The household's name. */
    public function handle(User $user, int $householdId): string
    {
        $household = $this->households->find($householdId);

        if ($household === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $pivot = $this->memberships->find($user, $household);

        if ($pivot === null || $pivot->pending !== true) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $this->approveMember->handle($user, $household);

        return $household->name;
    }
}
