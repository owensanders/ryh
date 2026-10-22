<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class CalendarMonthData extends Data
{
    /**
     * @param  array<int, string>  $weekdayLabels
     * @param  array<int, CalendarDayData>  $days
     * @param  array<int, MemberData>  $members
     */
    public function __construct(
        public string $month,
        public string $monthLabel,
        public string $previousMonth,
        public string $nextMonth,
        public string $today,
        /** The household's date today, `YYYY-MM-DD`: nothing can be added before it. */
        public string $todayDate,
        public array $weekdayLabels,
        public array $days,
        public array $members,
    ) {}
}
