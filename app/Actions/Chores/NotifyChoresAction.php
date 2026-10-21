<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Exceptions\ChoreReminderCooldownException;
use App\Models\Household;
use App\Notifications\ChoresReminderNotification;

/** Reminds every member with at least one outstanding chore, then starts the household's cooldown. */
class NotifyChoresAction
{
    public function handle(Household $household): void
    {
        $availableAt = $household->choreRemindersAvailableAt();

        if ($availableAt !== null) {
            throw new ChoreReminderCooldownException($availableAt);
        }

        foreach ($household->chores()->outstandingAssigned()->get()->groupBy('assigned_to') as $chores) {
            $chores->first()?->assignee?->notify(new ChoresReminderNotification($household, $chores->count()));
        }

        $household->update(['last_chore_notification_sent_at' => now()]);
    }
}
