<?php

declare(strict_types=1);

namespace App\UseCases\Calendar;

use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Data\CalendarDayData;
use App\Data\CalendarEventData;
use App\Data\CalendarMonthData;
use App\Data\MemberData;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;

class GetCalendarMonthUseCase
{
    /** Six rows of seven, so the grid never changes height as you page through months. */
    private const int CELLS = 42;

    public function __construct(
        private readonly CalendarEventRepositoryInterface $events,
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    public function execute(Household $household, CarbonImmutable $month): CalendarMonthData
    {
        $today = CarbonImmutable::today();
        $first = $month->startOfMonth();
        $start = $first->startOfWeek();
        $end = $start->addDays(self::CELLS - 1)->endOfDay();

        $eventsByDate = [];

        foreach ($this->events->between($household, $start, $end) as $event) {
            $spanStart = $event->starts_at->toImmutable()->max($start)->startOfDay();
            $spanEnd = ($event->ends_at ?? $event->starts_at)->toImmutable()->min($end)->startOfDay();

            for ($date = $spanStart; $date->lte($spanEnd); $date = $date->addDay()) {
                $eventsByDate[$date->toDateString()][] = $event;
            }
        }

        $days = [];
        $weekdayLabels = [];

        for ($offset = 0; $offset < self::CELLS; $offset++) {
            $day = $start->addDays($offset);
            $key = $day->toDateString();

            if ($offset < 7) {
                $weekdayLabels[] = $day->format('D');
            }

            $days[] = new CalendarDayData(
                date: $key,
                dayLabel: $day->format('D'),
                dateLabel: $day->format('j'),
                isToday: $day->isSameDay($today),
                isCurrentMonth: $day->month === $first->month,
                events: collect($eventsByDate[$key] ?? [])
                    ->map(fn (CalendarEvent $event) => CalendarEventData::fromModel($event, $key))
                    ->values()
                    ->all(),
            );
        }

        return new CalendarMonthData(
            month: $first->format('Y-m'),
            monthLabel: $first->format('F Y'),
            previousMonth: $first->subMonth()->format('Y-m'),
            nextMonth: $first->addMonth()->format('Y-m'),
            today: $today->format('Y-m'),
            weekdayLabels: $weekdayLabels,
            days: $days,
            members: $this->memberships->activeMembers($household)
                ->map(fn (User $member) => MemberData::fromModel($member))
                ->values()
                ->all(),
        );
    }
}
