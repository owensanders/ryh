<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Inputs\CalendarEventInput;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use App\Notifications\CalendarEventUpdatedNotification;
use App\Support\RecipientsHelper;
use Illuminate\Support\Facades\DB;

class UpdateCalendarEventAction
{
    public function handle(Household $household, CalendarEvent $event, CalendarEventInput $input, User $actor): CalendarEvent
    {
        $previousAttendeeIds = $event->attendees()->pluck('users.id')->map(fn (mixed $id): int => (int) $id)->all();

        DB::transaction(function () use ($household, $event, $input): void {
            $event->update([
                'title' => $input->title,
                'starts_at' => $household->momentAt($input->startsAt),
                'ends_at' => ! $input->isAllDay && $input->endsAt !== null ? $household->momentAt($input->endsAt) : null,
                'is_all_day' => $input->isAllDay,
                'location' => $input->location,
                'notes' => $input->notes,
                'colour' => $input->colour,
            ]);
            $event->attendees()->sync($input->attendees);
        });

        $event->refresh()->load('attendees');

        $this->notifyInterestedParties($household, $event, $previousAttendeeIds, $actor);

        return $event;
    }

    /**
     * Newly added attendees get an "assigned" notification; everyone else who already
     * had this event (attendees and creator) gets a plain "updated" one.
     *
     * @param  array<int, int>  $previousAttendeeIds
     */
    private function notifyInterestedParties(Household $household, CalendarEvent $event, array $previousAttendeeIds, User $actor): void
    {
        // Someone newly added hears they've been assigned; everyone else that it changed.
        foreach (RecipientsHelper::except($actor, [...$event->attendees, $event->creator]) as $recipient) {
            $isNewAttendee = $event->attendees->contains('id', $recipient->id) && ! in_array($recipient->id, $previousAttendeeIds, true);

            $recipient->notify($isNewAttendee
                ? new CalendarEventAssignedNotification($household, $event, $actor)
                : new CalendarEventUpdatedNotification($household, $event, $actor));
        }
    }
}
