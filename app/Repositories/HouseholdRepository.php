<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HouseholdRepository implements HouseholdRepositoryInterface
{
    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Household
    {
        return Household::create($attributes);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(Household $household, array $attributes): Household
    {
        $household->update($attributes);

        return $household->refresh();
    }

    public function lockForUpdate(Household $household): Household
    {
        return Household::query()->whereKey($household->id)->lockForUpdate()->firstOrFail();
    }

    public function findByJoinCode(string $joinCode): ?Household
    {
        return Household::where('join_code', $joinCode)->first();
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

    public function findByStripeCustomer(string $customerId): ?Household
    {
        return Household::where('stripe_id', $customerId)->first();
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

    /** @return Collection<int, CalendarEvent> */
    public function eventsBetween(Household $household, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $household->calendarEvents()
            ->with('attendees')
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get();
    }

    /** @return Collection<int, BudgetCategory> */
    public function budgetFor(Household $household, CarbonImmutable $month): Collection
    {
        return $household->budgetCategories()
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->get();
    }

    /** @return Collection<int, BudgetCategory> */
    public function budgetCategoriesUpTo(Household $household, CarbonImmutable $month): Collection
    {
        return $household->budgetCategories()
            ->whereDate('month', '<=', $month->startOfMonth()->toDateString())
            ->orderByDesc('month')
            ->get();
    }

    public function countAiMealPlansSince(CarbonInterface $since): int
    {
        return Household::where('ai_meal_plan_generated_at', '>=', $since)->count();
    }

    public function delete(Household $household): void
    {
        $household->delete();
    }
}
