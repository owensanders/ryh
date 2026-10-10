<?php

declare(strict_types=1);

namespace App\UseCases\Calendar;

use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Data\CalendarEventData;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use App\Notifications\CalendarEventUpdatedNotification;

class UpdateCalendarEventUseCase
{
    public function __construct(private readonly CalendarEventRepositoryInterface $events) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $attendeeIds
     */
    public function execute(CalendarEvent $event, array $attributes, array $attendeeIds, User $actor): CalendarEventData
    {
        $previousAttendeeIds = $event->attendees()->pluck('users.id')->map(fn (mixed $id): int => (int) $id)->all();

        $updated = $this->events->update($event, $attributes, $attendeeIds);

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
        $notified = [$actor->id];

        foreach ($event->attendees as $attendee) {
            if ($attendee->id === $actor->id || in_array($attendee->id, $notified, true)) {
                continue;
            }

            $notification = in_array($attendee->id, $previousAttendeeIds, true)
                ? new CalendarEventUpdatedNotification($event, $actor)
                : new CalendarEventAssignedNotification($event, $actor);

            $attendee->notify($notification);
            $notified[] = $attendee->id;
        }

        if ($event->creator !== null && ! in_array($event->creator->id, $notified, true)) {
            $event->creator->notify(new CalendarEventUpdatedNotification($event, $actor));
        }
    }
}
