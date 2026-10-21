<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\PaletteEnum;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use App\Notifications\CalendarEventAssignedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AddCalendarEventAction
{
    /**
     * @param  array<string, mixed>  $data  CalendarEventRequest's validated input: title, starts_at,
     *                                      ends_at (left out when all-day), is_all_day, location,
     *                                      notes, colour, attendees (empty for the whole house).
     */
    public function handle(Household $household, array $data, User $actor): CalendarEvent
    {
        $isAllDay = (bool) ($data['is_all_day'] ?? false);

        $event = DB::transaction(function () use ($household, $data, $actor, $isAllDay): CalendarEvent {
            $event = $household->calendarEvents()->create([
                'title' => $data['title'],
                'starts_at' => CarbonImmutable::parse($data['starts_at']),
                'ends_at' => ! $isAllDay && isset($data['ends_at']) ? CarbonImmutable::parse($data['ends_at']) : null,
                'is_all_day' => $isAllDay,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'colour' => PaletteEnum::from($data['colour']),
                'created_by' => $actor->id,
            ]);
            $event->attendees()->sync($data['attendees'] ?? []);

            return $event->load('attendees');
        });

        foreach ($event->attendees as $attendee) {
            if ($attendee->id !== $actor->id) {
                $attendee->notify(new CalendarEventAssignedNotification($event, $actor));
            }
        }

        return $event;
    }
}
