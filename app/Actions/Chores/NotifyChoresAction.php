<?php

declare(strict_types=1);

namespace App\Actions\Chores;

use App\Exceptions\ChoreReminderCooldownException;
use App\Models\Household;
use App\Notifications\ChoresReminderNotification;
use Illuminate\Support\Facades\DB;

/** Reminds every member with at least one outstanding chore, then starts the household's cooldown. */
class NotifyChoresAction
{
    /**
     * Under the household's lock, so two people pressing it at once can't
     * both pass the cooldown and remind everyone twice. The reminders' bell
     * entries are written in the same transaction; their pushes and live
     * updates are queued for after it commits.
     *
     * @throws ChoreReminderCooldownException
     */
    public function handle(Household $household): void
    {
        DB::transaction(function () use ($household): void {
            $household = $household->lockRow();
            $availableAt = $household->choreRemindersAvailableAt();

            if ($availableAt !== null) {
                throw new ChoreReminderCooldownException($availableAt);
            }

            $household->update(['last_chore_notification_sent_at' => now()]);

            foreach ($household->chores()->outstandingAssigned()->get()->groupBy('assigned_to') as $chores) {
                $chores->first()?->assignee?->notify(new ChoresReminderNotification($household, $chores->count()));
            }
        });
    }
}
