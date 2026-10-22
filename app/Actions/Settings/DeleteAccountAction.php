<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\House\CancelHouseholdSubscriptionAction;
use App\Actions\House\DeleteHouseholdAction;
use App\Enums\HouseholdRoleEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAccountAction
{
    public function __construct(
        private readonly CancelHouseholdSubscriptionAction $cancelSubscription,
        private readonly DeleteHouseholdAction $deleteHousehold,
    ) {}

    /** @throws ValidationException */
    public function handle(User $user, bool $confirmHouseholdDeletion = false): void
    {
        $goingWithThem = $this->householdsGoingWithThem($user, $confirmHouseholdDeletion);

        // Stripe can't take part in the transaction, so their subscriptions are
        // stopped first: a failure here leaves the account and households intact.
        foreach ($goingWithThem as $household) {
            $this->cancelSubscription->handle($household);
        }

        Auth::logout();

        DB::transaction(function () use ($user, $goingWithThem): void {
            foreach ($goingWithThem as $household) {
                $this->deleteHousehold->handle($household);
            }

            // push_subscriptions is polymorphic, so nothing cascades from users.
            $user->pushSubscriptions()->delete();
            $user->delete();
        });

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Every household they're the only owner of, in any of their households,
     * so none is left behind with nobody able to run it.
     *
     * @return list<Household>
     *
     * @throws ValidationException when others would lose a household too and they haven't confirmed that
     */
    private function householdsGoingWithThem(User $user, bool $confirmHouseholdDeletion): array
    {
        $goingWithThem = [];
        $sharedWithOthers = [];

        foreach ($user->households()->withCount('members')->with(['members', 'subscriptions'])->get() as $household) {
            $members = $household->members;
            $isOwner = $members->contains(fn (User $m) => $m->id === $user->id && $m->pivot?->role === HouseholdRoleEnum::Owner);
            $hasOtherOwners = $members->contains(fn (User $m) => $m->id !== $user->id && $m->pivot?->role === HouseholdRoleEnum::Owner);

            if (! $isOwner || $hasOtherOwners) {
                continue;
            }

            $goingWithThem[] = $household;

            if ($members->contains(fn (User $m) => $m->id !== $user->id)) {
                $sharedWithOthers[] = $household->name;
            }
        }

        if ($sharedWithOthers !== [] && ! $confirmHouseholdDeletion) {
            $which = count($sharedWithOthers) === 1 ? 'this household' : 'these households ('.implode(', ', $sharedWithOthers).')';
            $them = count($sharedWithOthers) === 1 ? 'the household and all its data' : 'them and all their data';

            throw ValidationException::withMessages([
                'household' => "You're the sole owner of {$which}. Deleting your account will permanently delete {$them} (chores, meals, shopping lists, calendar, budget, documents). Consider promoting another member to Owner instead, or confirm to delete everything.",
            ]);
        }

        return $goingWithThem;
    }
}
