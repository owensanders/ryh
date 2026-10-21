<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Data\CalendarEventData;
use App\Data\Input\CalendarEventInputData;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use App\Notifications\CalendarEventUpdatedNotification;
use App\Repositories\CalendarEventRepository;
use App\Support\RecipientsHelper;

class UpdateCalendarEventAction
{
    public function __construct(private readonly CalendarEventRepository $events) {}

    public function handle(CalendarEvent $event, CalendarEventInputData $changes, User $actor): CalendarEventData
    {
        $previousAttendeeIds = $this->events->attendeeIds($event);

        $updated = $this->events->update($event, $changes);

        $this->notifyInterestedParties($updated, $previousAttendeeIds, $actor);

        return CalendarEventData::fromModel($updated, $updated->starts_at->toDateString());
    }

    /**
     * Newly added attendees get an "assigned" notification; everyone else who already
     * had this event (attendees and creator) gets a plain "updated" one.
     *
     * @param  array<int, int>  $previousAttendeeIds
     */
    private function notifyInterestedParties(CalendarEvent $event, array $previousAttendeeIds, User $actor): void
    {
        // Someone newly added hears they've been assigned; everyone else that it changed.
        foreach (RecipientsHelper::except($actor, [...$event->attendees, $event->creator]) as $recipient) {
            $isNewAttendee = $event->attendees->contains('id', $recipient->id) && ! in_array($recipient->id, $previousAttendeeIds, true);

            $recipient->notify($isNewAttendee
                ? new CalendarEventAssignedNotification($event, $actor)
                : new CalendarEventUpdatedNotification($event, $actor));
        }
    }
}
