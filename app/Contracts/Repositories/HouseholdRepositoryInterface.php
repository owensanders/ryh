<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface HouseholdRepositoryInterface
{
    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Household;

    /** @param  array<string, mixed>  $attributes */
    public function update(Household $household, array $attributes): Household;

    public function findByJoinCode(string $joinCode): ?Household;

    /** @return Collection<int, User> */
    public function members(Household $household): Collection;

    /**
     * Approved members only — excludes anyone still `pending` approval/invite.
     *
     * @return Collection<int, User>
     */
    public function activeMembers(Household $household): Collection;

    /** True if `$member` is the only Owner-role member of `$household`. */
    public function isSoleOwner(Household $household, User $member): bool;

    /** @return Collection<int, CalendarEvent> */
    public function eventsBetween(Household $household, CarbonImmutable $from, CarbonImmutable $to): Collection;

    /** @return Collection<int, BudgetCategory> */
    public function budgetFor(Household $household, CarbonImmutable $month): Collection;

    /** @return Collection<int, BudgetCategory> Categories from the given month and earlier, newest month first. */
    public function budgetCategoriesUpTo(Household $household, CarbonImmutable $month): Collection;

    /** Households (app-wide) that generated an AI meal plan at or after the given moment. */
    public function countAiMealPlansSince(CarbonInterface $since): int;

    public function delete(Household $household): void;
}
