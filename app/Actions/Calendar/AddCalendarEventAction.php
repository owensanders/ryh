<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Inputs\CalendarEventInput;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use Illuminate\Support\Facades\DB;

class AddCalendarEventAction
{
    public function handle(Household $household, CalendarEventInput $input, User $actor): CalendarEvent
    {
        $event = DB::transaction(function () use ($household, $input, $actor): CalendarEvent {
            $event = $household->calendarEvents()->create([
                'title' => $input->title,
                'starts_at' => $household->momentAt($input->startsAt),
                'ends_at' => ! $input->isAllDay && $input->endsAt !== null ? $household->momentAt($input->endsAt) : null,
                'is_all_day' => $input->isAllDay,
                'location' => $input->location,
                'notes' => $input->notes,
                'colour' => $input->colour,
                'created_by' => $actor->id,
            ]);
            $event->attendees()->sync($input->attendees);

            return $event->load('attendees');
        });

        foreach ($event->attendees as $attendee) {
            if ($attendee->id !== $actor->id) {
                $attendee->notify(new CalendarEventAssignedNotification($household, $event, $actor));
            }
        }

        return $event;
    }
}
