<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\PaletteEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use App\Notifications\CalendarEventUpdatedNotification;
use App\Support\RecipientsHelper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdateCalendarEventAction
{
    /** @param  array<string, mixed>  $data  CalendarEventRequest's validated input; see AddCalendarEventAction. */
    public function handle(CalendarEvent $event, array $data, User $actor): CalendarEvent
    {
        $previousAttendeeIds = $event->attendees()->pluck('users.id')->map(fn (mixed $id): int => (int) $id)->all();
        $isAllDay = (bool) ($data['is_all_day'] ?? false);

        DB::transaction(function () use ($event, $data, $isAllDay): void {
            $event->update([
                'title' => $data['title'],
                'starts_at' => CarbonImmutable::parse($data['starts_at']),
                'ends_at' => ! $isAllDay && isset($data['ends_at']) ? CarbonImmutable::parse($data['ends_at']) : null,
                'is_all_day' => $isAllDay,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'colour' => PaletteEnum::from($data['colour']),
            ]);
            $event->attendees()->sync($data['attendees'] ?? []);
        });

        $event->refresh()->load('attendees');

        $this->notifyInterestedParties($event, $previousAttendeeIds, $actor);

        return $event;
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
