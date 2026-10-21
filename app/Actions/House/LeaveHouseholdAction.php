<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveHouseholdAction
{
    public function __construct(
        private readonly CancelHouseholdSubscriptionAction $cancelSubscription,
    ) {}

    /** @throws ValidationException */
    /** @return string The household's name, for saying they've left it. */
    public function handle(User $user, int $householdId, bool $confirmHouseholdDeletion = false): string
    {
        $household = Household::find($householdId);

        if ($household === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $membership = $household->membershipOf($user);

        if ($membership === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        if ($membership->role === HouseholdRoleEnum::Owner && $household->isSoleOwner($user)) {
            $hasOtherMembers = $household->members()->get()->contains(fn (User $m) => $m->id !== $user->id);

            if ($hasOtherMembers && ! $confirmHouseholdDeletion) {
                throw ValidationException::withMessages([
                    'household' => "You're the sole owner of this household. Leaving will permanently delete it and all its data (chores, meals, shopping lists, calendar, budget, documents). Consider promoting another member to Owner instead, or confirm to delete everything.",
                ]);
            }

            $this->cancelSubscription->handle($household);
            $household->delete();

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
