<?php

declare(strict_types=1);

namespace App\UseCases\Calendar;

use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Data\CalendarEventData;
use App\Models\Household;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;

class AddCalendarEventUseCase
{
    public function __construct(private readonly CalendarEventRepositoryInterface $events) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $attendeeIds
     */
    public function execute(Household $household, array $attributes, array $attendeeIds, User $actor): CalendarEventData
    {
        $attributes['created_by'] = $actor->id;

        $event = $this->events->create($household, $attributes, $attendeeIds);

        foreach ($event->attendees as $attendee) {
            if ($attendee->id !== $actor->id) {
                $attendee->notify(new CalendarEventAssignedNotification($event, $actor));
            }
        }

        return CalendarEventData::fromModel($event, $event->starts_at->toDateString());
    }
}
