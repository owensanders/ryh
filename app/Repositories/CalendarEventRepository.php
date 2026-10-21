<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Input\CalendarEventInputData;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarEventRepository
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

    public function create(Household $household, CalendarEventInputData $event, User $creator): CalendarEvent
    {
        return DB::transaction(function () use ($household, $event, $creator): CalendarEvent {
            $created = $household->calendarEvents()->create([...$this->columns($event), 'created_by' => $creator->id]);
            $created->attendees()->sync($event->attendeeIds);

            return $created->load('attendees');
        });
    }

    public function update(CalendarEvent $event, CalendarEventInputData $changes): CalendarEvent
    {
        return DB::transaction(function () use ($event, $changes): CalendarEvent {
            $event->update($this->columns($changes));
            $event->attendees()->sync($changes->attendeeIds);

            return $event->refresh()->load('attendees');
        });
    }

    /** @return array<int, int> */
    public function attendeeIds(CalendarEvent $event): array
    {
        return $event->attendees()->pluck('users.id')->map(fn (mixed $id): int => (int) $id)->values()->all();
    }

    public function delete(CalendarEvent $event): void
    {
        $event->delete();
    }

    /**
     * Takes this member off this household's events: ones they were the sole
     * attendee of are deleted, the rest just lose them. Other households' events are untouched.
     */
    public function removeAttendeeFrom(User $member, Household $household): void
    {
        $member->attendingEvents()->where('household_id', $household->id)->with('attendees')->get()
            ->each(fn (CalendarEvent $event) => $event->attendees->count() === 1
                ? $event->delete()
                : $event->attendees()->detach($member->id));
    }

    /** @return array<string, mixed> */
    private function columns(CalendarEventInputData $event): array
    {
        return [
            'title' => $event->title,
            'starts_at' => $event->startsAt,
            'ends_at' => $event->endsAt,
            'is_all_day' => $event->isAllDay,
            'location' => $event->location,
            'notes' => $event->notes,
            'colour' => $event->colour,
        ];
    }
}
