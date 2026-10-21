<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Collection;

class MembershipRepository
{
    public function find(User $user, Household $household): ?HouseholdUser
    {
        return HouseholdUser::query()
            ->where('user_id', $user->id)
            ->where('household_id', $household->id)
            ->first();
    }

    /** Their membership of the household they were last in, if they still have one. */
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

    /** The household to fall back to when they leave their current one, if they have another. */
    public function firstHouseholdIdFor(User $user): ?int
    {
        return $user->households()->first()?->id;
    }

    /** @return Collection<int, Household> Their households, each with its membership as `pivot` and its members loaded. */
    public function householdsWithMembersFor(User $user): Collection
    {
        return $user->households()->withCount('members')->with(['members', 'subscriptions'])->get();
    }

    /**
     * Everyone in the household, pending invites and join requests included, each with their membership as `pivot`.
     *
     * @return Collection<int, User>
     */
    public function members(Household $household): Collection
    {
        return $household->members()->get();
    }

    /**
     * Approved members only — excludes anyone still `pending` approval/invite.
     *
     * @return Collection<int, User>
     */
    public function activeMembers(Household $household): Collection
    {
        return $household->members()->wherePivot('pending', false)->get();
    }

    /** @return Collection<int, User> Owners who have joined (not pending) — who billing emails go to. */
    public function activeOwners(Household $household): Collection
    {
        return $household->members()
            ->wherePivot('pending', false)
            ->wherePivot('role', HouseholdRoleEnum::Owner->value)
            ->get();
    }

    /** True if `$member` is the only Owner-role member of `$household`. */
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

    /** True once the household has no room left — pending invites and join requests hold a place too. */
    public function isFull(Household $household): bool
    {
        return $this->count($household) >= config('plans.max_members');
    }

    /** A Teen or Child needs a guardian's confirmation; see HouseholdUser::guardianAttributes(). */
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
