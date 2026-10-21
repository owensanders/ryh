<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Data\CalendarEventData;
use App\Data\Input\CalendarEventInputData;
use App\Models\Household;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use App\Repositories\CalendarEventRepository;

class AddCalendarEventAction
{
    public function __construct(private readonly CalendarEventRepository $events) {}

    public function handle(Household $household, CalendarEventInputData $input, User $actor): CalendarEventData
    {
        $event = $this->events->create($household, $input, $actor);

        foreach ($event->attendees as $attendee) {
            if ($attendee->id !== $actor->id) {
                $attendee->notify(new CalendarEventAssignedNotification($event, $actor));
            }
        }

        return CalendarEventData::fromModel($event, $event->starts_at->toDateString());
    }
}
