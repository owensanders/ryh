<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Models\User;
use App\Repositories\HouseholdRepository;
use Carbon\CarbonImmutable;

class RecordLoginStreakUseCase
{
    public function __construct(
        private readonly HouseholdRepository $households,
    ) {}

    public function execute(User $user): void
    {
        $household = $user->lastHousehold;

        if ($household === null) {
            return;
        }

        $today = CarbonImmutable::today('UTC');
        $lastActive = $household->streak_last_active_date;

        if ($lastActive !== null && $today->isSameDay($lastActive)) {
            return;
        }

        $continuesStreak = $lastActive !== null && $today->subDay()->isSameDay($lastActive);

        $this->households->recordStreak($household, $continuesStreak ? $household->streak_days + 1 : 1, $today);
    }
}
