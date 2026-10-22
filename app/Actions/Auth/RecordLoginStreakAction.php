<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Carbon\CarbonImmutable;

class RecordLoginStreakAction
{
    /**
     * Each day they sign in keeps their own streak going; a day missed starts
     * it again. Days turn over at midnight in their household's time zone.
     */
    public function handle(User $user): void
    {
        $today = $user->lastHousehold?->today() ?? CarbonImmutable::today();
        $lastActive = $user->streak_last_active_date;

        if ($lastActive !== null && $today->isSameDay($lastActive)) {
            return;
        }

        $continuesStreak = $lastActive !== null && $today->subDay()->isSameDay($lastActive);

        $user->update(['streak_days' => $continuesStreak ? $user->streak_days + 1 : 1, 'streak_last_active_date' => $today]);
    }
}
