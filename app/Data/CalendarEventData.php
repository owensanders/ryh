<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class CalendarEventData extends Data
{
    /** @param array<int, MemberData> $attendees */
    public function __construct(
        public int $id,
        public string $title,
        public string $date,
        public string $time,
        public string $who,
        public bool $isAllDay,
        public ?string $location,
        public ?string $notes,
        public string $colour,
        public string $colourKey,
        public array $attendees,
        public bool $isSpanStart,
        public bool $isSpanEnd,
        /** Raw values for the edit form, which needs to round-trip them back. */
        public string $startsAt,
        public ?string $endsAt,
        public AbilitiesData $can,
        /** Over already, so it can't be changed any more. One still running can. */
        public bool $hasEnded,
    ) {}

    public static function fromModel(CalendarEvent $event, string $forDate, CarbonImmutable $now): self
    {
        return new self(
            id: $event->id,
            title: $event->title,
            date: $forDate,
            time: $event->timeLabel(),
            who: $event->whoLabel(),
            isAllDay: $event->is_all_day,
            location: $event->location,
            notes: $event->notes,
            colour: $event->colour->cssVar(),
            colourKey: $event->colour->value,
            attendees: $event->attendees->map(MemberData::fromModel(...))->values()->all(),
            isSpanStart: $forDate === $event->starts_at->toDateString(),
            isSpanEnd: $forDate === ($event->ends_at ?? $event->starts_at)->toDateString(),
            startsAt: $event->starts_at->format('Y-m-d\TH:i'),
            endsAt: $event->ends_at?->format('Y-m-d\TH:i'),
            can: AbilitiesData::for($event, 'update', 'delete'),
            hasEnded: $event->hasEndedBy($now),
        );
    }
}
