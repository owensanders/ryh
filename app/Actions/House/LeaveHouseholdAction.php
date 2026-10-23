<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Exceptions\MembershipException;
use App\Inputs\LeaveHouseholdInput;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class LeaveHouseholdAction
{
    public function __construct(
        private readonly CancelHouseholdSubscriptionAction $cancelSubscription,
        private readonly DeleteHouseholdAction $deleteHousehold,
    ) {}

    /**
     * @return string The household's name, for saying they've left it.
     *
     * @throws MembershipException
     */
    public function handle(User $user, LeaveHouseholdInput $input): string
    {
        $household = Household::find($input->householdId);

        if ($household === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$input->householdId]);
        }

        $membership = $household->membershipOf($user);

        if ($membership === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$input->householdId]);
        }

        if ($membership->role === HouseholdRoleEnum::Owner && $household->isSoleOwner($user)) {
            $hasOtherMembers = $household->members()->get()->contains(fn (User $m) => $m->id !== $user->id);

            if ($hasOtherMembers && ! $input->confirmHouseholdDeletion) {
                throw MembershipException::soleOwnerLeaving();
            }

            $this->cancelSubscription->handle($household);
            $this->deleteHousehold->handle($household);

            return $household->name;
        }

        DB::transaction(function () use ($user, $household): void {
            $wasCurrent = $user->last_household_id === $household->id;

            $household->members()->detach($user->id);

            if ($wasCurrent) {
                $user->rememberHousehold($user->firstHouseholdId());
            }
        });

        return $household->name;
    }
}
