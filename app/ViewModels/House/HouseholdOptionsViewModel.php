<?php

declare(strict_types=1);

namespace App\ViewModels\House;

use App\Data\HouseholdOptionData;
use App\Models\Household;
use App\Models\User;
use App\Repositories\MembershipRepository;
use LogicException;

class HouseholdOptionsViewModel
{
    public function __construct(
        private readonly MembershipRepository $memberships,
    ) {}

    /** @return array<int, HouseholdOptionData> */
    public function toData(User $user): array
    {
        $households = $this->memberships->householdsWithMembersFor($user);

        return $households->map(function (Household $household) {
            $membership = $household->pivot ?? throw new LogicException('A household loaded through its user always has the membership.');

            return new HouseholdOptionData(
                id: $household->id,
                name: $household->name,
                role: $membership->role->value,
                roleLabel: $membership->role->label(),
                memberCount: $household->members_count,
                memberAvatars: $household->members->take(4)
                    ->map(fn (User $member) => [
                        'initials' => $member->initials ?? mb_strtoupper(mb_substr($member->name, 0, 2)),
                        'colour' => $member->colour->cssVar(),
                    ])
                    ->values()
                    ->all(),
            );
        })->values()->all();
    }
}
