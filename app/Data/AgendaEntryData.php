<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\CalendarEvent;
use Spatie\LaravelData\Data;

class AgendaEntryData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $time,
        public ?string $who,
        public string $colour,
    ) {}

    /** `$timezone` is the household's, whose clock the time is shown on. */
    public static function fromModel(CalendarEvent $event, string $timezone): self
    {
        return new self(
            id: $event->id,
            title: $event->title,
            time: $event->timeLabel($timezone),
            who: $event->whoLabel(),
            colour: $event->colour->cssVar(),
        );
    }
}
