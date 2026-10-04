<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRole;
use App\Models\User;
use App\UseCases\House\CancelHouseholdSubscriptionUseCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeleteAccountUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly HouseholdRepositoryInterface $households,
        private readonly CancelHouseholdSubscriptionUseCase $cancelSubscription,
    ) {}

    /** @throws ValidationException */
    public function execute(User $user, bool $confirmHouseholdDeletion = false): void
    {
        if ($user->currentHousehold !== null && $user->currentRole() === HouseholdRole::Owner) {
            $this->deleteHouseholdIfSoleOwner($user, $confirmHouseholdDeletion);
        }

        Auth::logout();

        $this->users->delete($user);

        session()->invalidate();
        session()->regenerateToken();
    }

    /** @throws ValidationException */
    private function deleteHouseholdIfSoleOwner(User $user, bool $confirmHouseholdDeletion): void
    {
        $members = $this->households->members($user->currentHousehold);
        $hasOtherOwners = $members->contains(fn (User $m) => $m->pivot->role === HouseholdRole::Owner && $m->id !== $user->id);

        if ($hasOtherOwners) {
            return;
        }

        $hasOtherMembers = $members->contains(fn (User $m) => $m->id !== $user->id);

        if ($hasOtherMembers && ! $confirmHouseholdDeletion) {
            throw ValidationException::withMessages([
                'household' => "You're the sole owner of this household. Deleting your account will permanently delete the household and all its data (chores, meals, shopping lists, calendar, budget, documents). Consider promoting another member to Owner instead, or confirm to delete everything.",
            ]);
        }

        $this->cancelSubscription->execute($user->currentHousehold);
        $this->households->delete($user->currentHousehold);
    }
}
