<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcceptHouseholdInviteAction
{
    public function __construct(
        private readonly ApproveMemberAction $approveMember,
    ) {}

    /** @return string The household's name. */
    public function handle(User $user, int $householdId): string
    {
        $household = Household::find($householdId);

        if ($household === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $pivot = $household->membershipOf($user);

        if ($pivot === null || $pivot->pending !== true) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $this->approveMember->handle($user, $household);

        return $household->name;
    }
}
