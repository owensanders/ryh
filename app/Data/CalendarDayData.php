<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * One cell of the month grid. Leading and trailing cells belong to the
 * neighbouring months and are flagged with `isCurrentMonth = false`.
 */
class CalendarDayData extends Data
{
    /** @param array<int, CalendarEventData> $events */
    public function __construct(
        public string $date,
        public string $dayLabel,
        public string $dateLabel,
        public bool $isToday,
        public bool $isCurrentMonth,
        public array $events,
    ) {}
}
