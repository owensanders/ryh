<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Exceptions\MembershipException;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveMemberAction
{
    /**
     * Covers both removing an active member and cancelling a pending invite.
     * Takes them out of this household only — their account and any other
     * households they're in are untouched. Here, chores assigned to them and
     * events they were the only attendee of are deleted; they're taken off
     * events with other attendees and off planned meals they were cooking.
     *
     * @throws MembershipException
     */
    /** @return bool Whether they were still pending: an invite cancelled rather than a member removed. */
    public function handle(User $member, Household $household): bool
    {
        $membership = $household->membershipOf($member);
        $currentRole = $membership?->role;

        if ($currentRole === HouseholdRoleEnum::Owner && $household->isSoleOwner($member)) {
            throw MembershipException::soleOwnerCantBeRemoved();
        }

        DB::transaction(function () use ($member, $household): void {
            $household->chores()->assignedTo($member)->delete();
            // Events only they were going to go; the rest just lose them.
            $member->attendingEvents()->where('household_id', $household->id)->with('attendees')->get()
                ->each(fn (CalendarEvent $event) => $event->attendees->count() === 1
                    ? $event->delete()
                    : $event->attendees()->detach($member->id));
            // Their meals stay planned, just with nobody cooking.
            $household->plannedMeals()->where('cook_id', $member->id)->update(['cook_id' => null]);

            $household->members()->detach($member->id);

            if ($member->last_household_id === $household->id) {
                $member->rememberHousehold($member->firstHouseholdId());
            }
        });

        return $membership?->pending === true;
    }
}
