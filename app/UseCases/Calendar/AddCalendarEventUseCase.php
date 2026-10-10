<?php

declare(strict_types=1);

namespace App\UseCases\Calendar;

use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Data\CalendarEventData;
use App\Data\Input\CalendarEventInputData;
use App\Models\Household;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;

class AddCalendarEventUseCase
{
    public function __construct(private readonly CalendarEventRepositoryInterface $events) {}

    public function execute(Household $household, CalendarEventInputData $input, User $actor): CalendarEventData
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
