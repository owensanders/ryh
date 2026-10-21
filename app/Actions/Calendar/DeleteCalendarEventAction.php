<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\CalendarEventDeletedNotification;
use App\Support\RecipientsHelper;

class DeleteCalendarEventAction
{
    public function handle(CalendarEvent $event, User $actor): void
    {
        $this->notifyInterestedParties($event, $actor);

        $event->delete();
    }

    /** Tells attendees and/or the creator when someone else deletes their event, without notifying anyone twice. */
    private function notifyInterestedParties(CalendarEvent $event, User $actor): void
    {
        foreach (RecipientsHelper::except($actor, [...$event->attendees, $event->creator]) as $recipient) {
            $recipient->notify(new CalendarEventDeletedNotification($event, $actor));
        }
    }
}
