<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Data\Input\HouseholdSetupInputData;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use App\Repositories\HouseholdRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class CreateHouseholdUseCase
{
    public function __construct(
        private readonly HouseholdRepository $households,
        private readonly UserRepository $users,
        private readonly InviteMemberUseCase $inviteMember,
        private readonly MembershipRepository $memberships,
    ) {}

    public function execute(User $owner, HouseholdSetupInputData $setup): Household
    {
        // The household, its owner's membership and their claimed trial stand
        // or fall together — a household with no owner would strand them
        // with their one free trial spent.
        $household = DB::transaction(function () use ($owner, $setup): Household {
            // One free month per person — otherwise a lapsed owner could just
            // start a new household to reset the clock.
            $household = $this->households->create(
                trim($setup->name),
                trialEndsAt: $owner->trial_claimed_at === null ? now()->addDays(config('plans.trial_days')) : null,
            );

            $this->users->moveIntoHousehold($owner, $household, PaletteEnum::cases()[0]);
            $this->users->claimTrial($owner);

            $this->memberships->add($owner, $household, HouseholdRoleEnum::Owner);

            return $household;
        });

        foreach ($setup->invites as $invite) {
            $this->inviteMember->execute($household, $invite, invitedBy: $owner);
        }

        return $household;
    }
}
