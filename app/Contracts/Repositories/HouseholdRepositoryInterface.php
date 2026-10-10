<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Household;
use Carbon\CarbonInterface;

interface HouseholdRepositoryInterface
{
    public function create(string $name, ?CarbonInterface $trialEndsAt): Household;

    public function setJoinCodeEnabled(Household $household, bool $enabled): Household;

    public function setJoinCode(Household $household, string $joinCode): Household;

    public function recordStreak(Household $household, int $days, CarbonInterface $lastActiveOn): void;

    public function markAiMealPlanGenerated(Household $household): void;

    public function markChoreRemindersSent(Household $household): void;

    public function find(int $id): ?Household;

    public function findByJoinCode(string $joinCode): ?Household;

    /**
     * Re-reads the household holding a row lock until the transaction ends,
     * so checks like the member cap can't race another request.
     */
    public function lockForUpdate(Household $household): Household;

    /** The household billed under this Stripe customer ID, as named in webhook payloads. */
    public function findByStripeCustomer(string $customerId): ?Household;

    /** Households (app-wide) that generated an AI meal plan at or after the given moment. */
    public function countAiMealPlansSince(CarbonInterface $since): int;

    public function delete(Household $household): void;
}
