<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Data\InviteJoinResultData;
use App\Enums\HouseholdRoleEnum;
use App\Enums\InviteJoinOutcomeEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/** Someone who registered from an invite link joins the household it was for. */
class JoinHouseholdFromInviteUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
        private readonly UserRepositoryInterface $users,
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    public function execute(
        User $user,
        int $householdId,
        HouseholdRoleEnum $role,
        ?int $guardianId = null,
        ?DateTimeInterface $guardianConfirmedAt = null,
    ): InviteJoinResultData {
        $household = $this->households->find($householdId);

        if ($household === null) {
            return new InviteJoinResultData(InviteJoinOutcomeEnum::HouseholdGone, null);
        }

        // Links sent before guardians had to confirm can't add a Teen or Child.
        if ($role->isForMinors() && $guardianId === null) {
            return new InviteJoinResultData(InviteJoinOutcomeEnum::NeedsGuardian, $household->name);
        }

        // Under the household's lock, so two people can't both take its last place.
        $joined = DB::transaction(function () use ($user, $household, $role, $guardianId, $guardianConfirmedAt): bool {
            $household = $this->households->lockForUpdate($household);

            if ($this->memberships->isFull($household)) {
                return false;
            }

            $this->users->moveIntoHousehold($user, $household, $this->nextColour($household));
            $this->memberships->add($user, $household, $role, guardianId: $guardianId, guardianConfirmedAt: $guardianConfirmedAt);

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

        return $colours[$this->memberships->count($household) % count($colours)];
    }
}
