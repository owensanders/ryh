<?php

declare(strict_types=1);

namespace App\UseCases\Chores;

use App\Enums\ChoreStatus;
use App\Models\Household;
use App\Notifications\ChoresReminderNotification;

/** Reminds every member with at least one outstanding chore, then starts the household's cooldown. */
class NotifyChoresUseCase
{
    public function execute(Household $household): void
    {
        $outstanding = $household->chores()
            ->whereNotNull('assigned_to')
            ->where('status', ChoreStatus::Today)
            ->get()
            ->groupBy('assigned_to');

        foreach ($outstanding as $chores) {
            $chores->first()->assignee?->notify(new ChoresReminderNotification($household, $chores->count()));
        }

        $household->update(['last_chore_notification_sent_at' => now()]);
    }
}
