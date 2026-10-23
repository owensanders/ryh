<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\InviteJoinOutcomeEnum;
use App\Enums\PaletteEnum;
use App\Inputs\PendingInviteInput;
use App\Models\Household;
use App\Models\User;
use App\Results\InviteJoinResult;
use Illuminate\Support\Facades\DB;

/** Someone who registered from an invite link joins the household it was for. */
class JoinHouseholdFromInviteAction
{
    public function handle(User $user, PendingInviteInput $invite): InviteJoinResult
    {
        $role = $invite->role;
        $guardianId = $invite->guardianId;
        $guardianConfirmedAt = $invite->guardianConfirmedAt;
        $household = Household::find($invite->householdId);

        if ($household === null) {
            return new InviteJoinResult(InviteJoinOutcomeEnum::HouseholdGone, null);
        }

        // Links sent before guardians had to confirm can't add a Teen or Child.
        if ($role->isForMinors() && $guardianId === null) {
            return new InviteJoinResult(InviteJoinOutcomeEnum::NeedsGuardian, $household->name);
        }

        // Under the household's lock, so two people can't both take its last place.
        $joined = DB::transaction(function () use ($user, $household, $role, $guardianId, $guardianConfirmedAt): bool {
            $household = $household->lockRow();

            if ($household->isFull()) {
                return false;
            }

            $user->moveInto($household, $this->nextColour($household));
            $household->addMember($user, $role, guardianId: $guardianId, guardianConfirmedAt: $guardianConfirmedAt);

            return true;
        });

        return new InviteJoinResult(
            $joined ? InviteJoinOutcomeEnum::Joined : InviteJoinOutcomeEnum::HouseholdFull,
            $household->name,
        );
    }

    private function nextColour(Household $household): PaletteEnum
    {
        $colours = PaletteEnum::cases();

        return $colours[$household->members()->count() % count($colours)];
    }
}
