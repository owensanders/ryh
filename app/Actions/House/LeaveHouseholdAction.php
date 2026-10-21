<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use App\Repositories\HouseholdRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveHouseholdAction
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly HouseholdRepository $households,
        private readonly CancelHouseholdSubscriptionAction $cancelSubscription,
        private readonly MembershipRepository $memberships,
    ) {}

    /** @throws ValidationException */
    /** @return string The household's name, for saying they've left it. */
    public function handle(User $user, int $householdId, bool $confirmHouseholdDeletion = false): string
    {
        $household = $this->households->find($householdId);

        if ($household === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $membership = $this->memberships->find($user, $household);

        if ($membership === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        if ($membership->role === HouseholdRoleEnum::Owner && $this->memberships->isSoleOwner($household, $user)) {
            $hasOtherMembers = $this->memberships->members($household)->contains(fn (User $m) => $m->id !== $user->id);

            if ($hasOtherMembers && ! $confirmHouseholdDeletion) {
                throw ValidationException::withMessages([
                    'household' => "You're the sole owner of this household. Leaving will permanently delete it and all its data (chores, meals, shopping lists, calendar, budget, documents). Consider promoting another member to Owner instead, or confirm to delete everything.",
                ]);
            }

            $this->cancelSubscription->handle($household);
            $this->households->delete($household);

            return $household->name;
        }

        DB::transaction(function () use ($user, $household): void {
            $wasCurrent = $user->last_household_id === $household->id;

            $this->memberships->remove($user, $household);

            if ($wasCurrent) {
                $this->users->setLastHousehold($user, $this->memberships->firstHouseholdIdFor($user));
            }
        });

        return $household->name;
    }
}
