<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Collection;

/** Who belongs to which household, and as what: the `household_user` table. */
interface MembershipRepositoryInterface
{
    public function find(User $user, Household $household): ?HouseholdUser;

    public function belongsTo(User $user, int $householdId): bool;

    public function hasAny(User $user): bool;

    /** @return Collection<int, int> */
    public function householdIdsFor(User $user): Collection;

    /** The household to fall back to when they leave their current one, if they have another. */
    public function firstHouseholdIdFor(User $user): ?int;

    /** @return Collection<int, Household> Their households, each with its membership as `pivot` and its members loaded. */
    public function householdsWithMembersFor(User $user): Collection;

    /**
     * Everyone in the household, pending invites and join requests included, each with their membership as `pivot`.
     *
     * @return Collection<int, User>
     */
    public function members(Household $household): Collection;

    /**
     * Approved members only — excludes anyone still `pending` approval/invite.
     *
     * @return Collection<int, User>
     */
    public function activeMembers(Household $household): Collection;

    /** @return Collection<int, User> Owners who have joined (not pending) — who billing emails go to. */
    public function activeOwners(Household $household): Collection;

    /** True if `$member` is the only Owner-role member of `$household`. */
    public function isSoleOwner(Household $household, User $member): bool;

    public function hasMemberWithEmail(Household $household, string $email): bool;

    public function count(Household $household): int;

    /** True once the household has no room left — pending invites and join requests hold a place too. */
    public function isFull(Household $household): bool;

    /** A Teen or Child needs a guardian's confirmation; see HouseholdUser::guardianAttributes(). */
    public function add(
        User $user,
        Household $household,
        HouseholdRoleEnum $role,
        bool $pending = false,
        ?PendingReasonEnum $pendingReason = null,
        ?int $guardianId = null,
        ?DateTimeInterface $guardianConfirmedAt = null,
    ): void;

    public function approve(User $member, Household $household): void;

    public function changeRole(User $member, Household $household, HouseholdRoleEnum $role, int $changedById): void;

    public function remove(User $member, Household $household): void;
}
