<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Models\Household;
use Carbon\CarbonInterface;

class HouseholdRepository implements HouseholdRepositoryInterface
{
    public function create(string $name, ?CarbonInterface $trialEndsAt): Household
    {
        return Household::create(['name' => $name, 'trial_ends_at' => $trialEndsAt]);
    }

    public function setJoinCodeEnabled(Household $household, bool $enabled): Household
    {
        $household->update(['join_code_enabled' => $enabled]);

        return $household->refresh();
    }

    public function setJoinCode(Household $household, string $joinCode): Household
    {
        $household->update(['join_code' => $joinCode]);

        return $household->refresh();
    }

    public function recordStreak(Household $household, int $days, CarbonInterface $lastActiveOn): void
    {
        $household->update(['streak_days' => $days, 'streak_last_active_date' => $lastActiveOn]);
    }

    public function markAiMealPlanGenerated(Household $household): void
    {
        $household->update(['ai_meal_plan_generated_at' => now()]);
    }

    public function markChoreRemindersSent(Household $household): void
    {
        $household->update(['last_chore_notification_sent_at' => now()]);
    }

    public function lockForUpdate(Household $household): Household
    {
        return Household::query()->whereKey($household->id)->lockForUpdate()->firstOrFail();
    }

    public function find(int $id): ?Household
    {
        return Household::find($id);
    }

    public function findBySlug(string $slug): ?Household
    {
        return Household::query()->where('slug', $slug)->first();
    }

    public function findByJoinCode(string $joinCode): ?Household
    {
        return Household::where('join_code', $joinCode)->first();
    }

    public function findByStripeCustomer(string $customerId): ?Household
    {
        return Household::where('stripe_id', $customerId)->first();
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
