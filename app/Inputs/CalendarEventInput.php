<?php

declare(strict_types=1);

namespace App\Inputs;

use App\Enums\PaletteEnum;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An event as CalendarEventRequest validated it. Its times are as the
 * household's clock reads them ('2026-10-25T18:00'); Household::momentAt()
 * makes them moments.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CalendarEventInput extends Dto
{
    /** @param  list<int>  $attendees  Empty for the whole house. */
    public function __construct(
        public readonly string $title,
        public readonly string $startsAt,
        public readonly PaletteEnum $colour,
        /** Left out for an all-day event, which has no end time. */
        public readonly ?string $endsAt = null,
        public readonly bool $isAllDay = false,
        public readonly ?string $location = null,
        public readonly ?string $notes = null,
        public readonly array $attendees = [],
    ) {}
}
