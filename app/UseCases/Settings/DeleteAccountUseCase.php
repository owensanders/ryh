<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use App\UseCases\House\CancelHouseholdSubscriptionUseCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $household = $user->currentHousehold;
        $deleteHousehold = $household !== null
            && $user->currentRole() === HouseholdRoleEnum::Owner
            && $this->householdGoesWithThem($user, $household, $confirmHouseholdDeletion);

        // Stripe can't take part in the transaction, so its subscription is
        // stopped first: a failure here leaves the account and household intact.
        if ($deleteHousehold) {
            $this->cancelSubscription->execute($household);
        }

        Auth::logout();

        DB::transaction(function () use ($user, $household, $deleteHousehold): void {
            if ($deleteHousehold) {
                $this->households->delete($household);
            }

            // push_subscriptions is polymorphic, so nothing cascades from users.
            $this->users->deleteAllPushSubscriptions($user);
            $this->users->delete($user);
        });

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * True when they're the household's only owner, so it can't outlive them.
     *
     * @throws ValidationException when others would lose it too and they haven't confirmed that
     */
    private function householdGoesWithThem(User $user, Household $household, bool $confirmHouseholdDeletion): bool
    {
        $members = $this->households->members($household);
        $hasOtherOwners = $members->contains(fn (User $m) => $m->pivot?->role === HouseholdRoleEnum::Owner && $m->id !== $user->id);

        if ($hasOtherOwners) {
            return false;
        }

        $hasOtherMembers = $members->contains(fn (User $m) => $m->id !== $user->id);

        if ($hasOtherMembers && ! $confirmHouseholdDeletion) {
            throw ValidationException::withMessages([
                'household' => "You're the sole owner of this household. Deleting your account will permanently delete the household and all its data (chores, meals, shopping lists, calendar, budget, documents). Consider promoting another member to Owner instead, or confirm to delete everything.",
            ]);
        }

        return true;
    }
}
