<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Collection;

class MembershipRepository implements MembershipRepositoryInterface
{
    public function find(User $user, Household $household): ?HouseholdUser
    {
        return HouseholdUser::query()
            ->where('user_id', $user->id)
            ->where('household_id', $household->id)
            ->first();
    }

    public function lastFor(User $user): ?HouseholdUser
    {
        if ($user->last_household_id === null) {
            return null;
        }

        return HouseholdUser::query()
            ->where('user_id', $user->id)
            ->where('household_id', $user->last_household_id)
            ->first();
    }

    public function belongsTo(User $user, int $householdId): bool
    {
        return $user->households()->where('households.id', $householdId)->exists();
    }

    public function hasAny(User $user): bool
    {
        return $user->households()->exists();
    }

    /** @return Collection<int, int> */
    public function householdIdsFor(User $user): Collection
    {
        return $user->households()->pluck('households.id')->map(fn (mixed $id): int => (int) $id);
    }

    public function firstHouseholdIdFor(User $user): ?int
    {
        return $user->households()->first()?->id;
    }

    /** @return Collection<int, Household> */
    public function householdsWithMembersFor(User $user): Collection
    {
        return $user->households()->withCount('members')->with(['members', 'subscriptions'])->get();
    }

    /** @return Collection<int, User> */
    public function members(Household $household): Collection
    {
        return $household->members()->get();
    }

    /** @return Collection<int, User> */
    public function activeMembers(Household $household): Collection
    {
        return $household->members()->wherePivot('pending', false)->get();
    }

    /** @return Collection<int, User> */
    public function activeOwners(Household $household): Collection
    {
        return $household->members()
            ->wherePivot('pending', false)
            ->wherePivot('role', HouseholdRoleEnum::Owner->value)
            ->get();
    }

    public function isSoleOwner(Household $household, User $member): bool
    {
        return $this->members($household)
            ->where('pivot.role', HouseholdRoleEnum::Owner)
            ->where('id', '!=', $member->id)
            ->isEmpty();
    }

    public function hasMemberWithEmail(Household $household, string $email): bool
    {
        return $household->members()->where('email', $email)->exists();
    }

    public function count(Household $household): int
    {
        return $household->members()->count();
    }

    public function isFull(Household $household): bool
    {
        return $this->count($household) >= config('plans.max_members');
    }

    public function add(
        User $user,
        Household $household,
        HouseholdRoleEnum $role,
        bool $pending = false,
        ?PendingReasonEnum $pendingReason = null,
        ?int $guardianId = null,
        ?DateTimeInterface $guardianConfirmedAt = null,
    ): void {
        $user->households()->attach($household->id, [
            'role' => $role,
            'pending' => $pending,
            'pending_reason' => $pendingReason,
            ...HouseholdUser::guardianAttributes($role, $guardianId, $guardianConfirmedAt),
        ]);
    }

    public function approve(User $member, Household $household): void
    {
        $member->households()->updateExistingPivot($household->id, [
            'pending' => false,
            'pending_reason' => null,
        ]);
    }

    public function changeRole(User $member, Household $household, HouseholdRoleEnum $role, int $changedById): void
    {
        $member->households()->updateExistingPivot($household->id, [
            'role' => $role,
            ...HouseholdUser::guardianAttributes($role, $changedById),
        ]);
    }

    public function remove(User $member, Household $household): void
    {
        $member->households()->detach($household->id);
    }
}
