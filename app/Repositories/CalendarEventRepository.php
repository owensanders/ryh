<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CalendarEventRepository implements CalendarEventRepositoryInterface
{
    /** @return Collection<int, CalendarEvent> */
    public function between(Household $household, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $household->calendarEvents()
            ->with('attendees')
            ->where('starts_at', '<=', $to)
            ->where(function ($query) use ($from) {
                $query->where('ends_at', '>=', $from)
                    ->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '>=', $from));
            })
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $attendeeIds
     */
    public function create(Household $household, array $attributes, array $attendeeIds): CalendarEvent
    {
        $event = $household->calendarEvents()->create($attributes);
        $event->attendees()->sync($attendeeIds);

        return $event->load('attendees');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $attendeeIds
     */
    public function update(CalendarEvent $event, array $attributes, array $attendeeIds): CalendarEvent
    {
        $event->update($attributes);
        $event->attendees()->sync($attendeeIds);

        return $event->refresh()->load('attendees');
    }

    public function delete(CalendarEvent $event): void
    {
        $event->delete();
    }

    public function removeAttendeeFrom(User $member, Household $household): void
    {
        $member->attendingEvents()->where('household_id', $household->id)->with('attendees')->get()
            ->each(fn (CalendarEvent $event) => $event->attendees->count() === 1
                ? $event->delete()
                : $event->attendees()->detach($member->id));
    }
}
