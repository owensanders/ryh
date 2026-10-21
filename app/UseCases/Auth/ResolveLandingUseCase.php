<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Data\LandingData;
use App\Models\User;
use App\Repositories\HouseholdRepository;
use App\Repositories\MembershipRepository;

/**
 * For /app and links from before households were in the URL: back to the
 * household they were last in, the only one they have, or the picker when
 * there's more than one and none to go back to.
 */
class ResolveLandingUseCase
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly HouseholdRepository $households,
    ) {}

    public function execute(User $user): LandingData
    {
        $householdIds = $this->memberships->householdIdsFor($user);

        if ($householdIds->isEmpty()) {
            return new LandingData(needsSetup: true, needsChoice: false, householdSlug: null);
        }

        $householdId = match (true) {
            $user->last_household_id !== null && $householdIds->contains($user->last_household_id) => $user->last_household_id,
            $householdIds->count() === 1 => $householdIds->first(),
            default => null,
        };

        return new LandingData(
            needsSetup: false,
            needsChoice: $householdId === null,
            householdSlug: $householdId !== null ? $this->households->find($householdId)?->slug : null,
        );
    }
}
