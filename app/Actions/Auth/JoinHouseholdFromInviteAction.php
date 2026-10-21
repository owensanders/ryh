<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\InviteJoinResultData;
use App\Enums\HouseholdRoleEnum;
use App\Enums\InviteJoinOutcomeEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/** Someone who registered from an invite link joins the household it was for. */
class JoinHouseholdFromInviteAction
{
    public function handle(
        User $user,
        int $householdId,
        HouseholdRoleEnum $role,
        ?int $guardianId = null,
        ?DateTimeInterface $guardianConfirmedAt = null,
    ): InviteJoinResultData {
        $household = Household::find($householdId);

        if ($household === null) {
            return new InviteJoinResultData(InviteJoinOutcomeEnum::HouseholdGone, null);
        }

        // Links sent before guardians had to confirm can't add a Teen or Child.
        if ($role->isForMinors() && $guardianId === null) {
            return new InviteJoinResultData(InviteJoinOutcomeEnum::NeedsGuardian, $household->name);
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

        return new InviteJoinResultData(
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
