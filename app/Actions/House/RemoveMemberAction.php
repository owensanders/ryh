<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use App\Repositories\CalendarEventRepository;
use App\Repositories\MealPlanRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveMemberAction
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly CalendarEventRepository $events,
        private readonly MealPlanRepository $meals,
        private readonly MembershipRepository $memberships,
    ) {}

    /**
     * Covers both removing an active member and cancelling a pending invite.
     * Takes them out of this household only — their account and any other
     * households they're in are untouched. Here, chores assigned to them and
     * events they were the only attendee of are deleted; they're taken off
     * events with other attendees and off planned meals they were cooking.
     *
     * @throws ValidationException
     */
    /** @return bool Whether they were still pending: an invite cancelled rather than a member removed. */
    public function handle(User $member, Household $household): bool
    {
        $membership = $this->memberships->find($member, $household);
        $currentRole = $membership?->role;

        if ($currentRole === HouseholdRoleEnum::Owner && $this->memberships->isSoleOwner($household, $member)) {
            throw ValidationException::withMessages(['member' => "You're the only owner — promote someone else first."]);
        }

        DB::transaction(function () use ($member, $household): void {
            $household->chores()->assignedTo($member)->delete();
            $this->events->removeAttendeeFrom($member, $household);
            $this->meals->unassignCook($household, $member);

            $this->memberships->remove($member, $household);

            if ($member->last_household_id === $household->id) {
                $this->users->setLastHousehold($member, $this->memberships->firstHouseholdIdFor($member));
            }
        });

        return $membership?->pending === true;
    }
}
