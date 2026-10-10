<?php

declare(strict_types=1);

namespace App\Data\Input;

use App\Enums\PaletteEnum;
use Carbon\CarbonImmutable;

/** An event as the add/edit form submits it, validated by CalendarEventRequest. */
final readonly class CalendarEventInputData
{
    /** @param  list<int>  $attendeeIds  Empty for a whole-house event. */
    public function __construct(
        public string $title,
        public CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public bool $isAllDay,
        public ?string $location,
        public ?string $notes,
        public PaletteEnum $colour,
        public array $attendeeIds,
    ) {}
}
