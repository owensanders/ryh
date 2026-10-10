<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LeaveHouseholdUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly HouseholdRepositoryInterface $households,
        private readonly CancelHouseholdSubscriptionUseCase $cancelSubscription,
    ) {}

    /** @throws ValidationException */
    public function execute(User $user, Household $household, bool $confirmHouseholdDeletion = false): void
    {
        $membership = $user->households()->where('households.id', $household->id)->first()?->pivot;

        abort_if($membership === null, 404);

        if ($membership->role === HouseholdRoleEnum::Owner && $this->households->isSoleOwner($household, $user)) {
            $hasOtherMembers = $this->households->members($household)->contains(fn (User $m) => $m->id !== $user->id);

            if ($hasOtherMembers && ! $confirmHouseholdDeletion) {
                throw ValidationException::withMessages([
                    'household' => "You're the sole owner of this household. Leaving will permanently delete it and all its data (chores, meals, shopping lists, calendar, budget, documents). Consider promoting another member to Owner instead, or confirm to delete everything.",
                ]);
            }

            $this->cancelSubscription->execute($household);
            $this->households->delete($household);

            return;
        }

        $wasCurrent = $user->current_household_id === $household->id;

        $user->households()->detach($household->id);

        if ($wasCurrent) {
            $this->users->update($user, ['current_household_id' => $user->households()->first()?->id]);
        }
    }
}
