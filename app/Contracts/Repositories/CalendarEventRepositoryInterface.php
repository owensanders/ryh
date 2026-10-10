<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Data\Input\CalendarEventInputData;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface CalendarEventRepositoryInterface
{
    /** @return Collection<int, CalendarEvent> */
    public function between(Household $household, CarbonImmutable $from, CarbonImmutable $to): Collection;

    public function create(Household $household, CalendarEventInputData $event, User $creator): CalendarEvent;

    public function update(CalendarEvent $event, CalendarEventInputData $changes): CalendarEvent;

    /** @return array<int, int> */
    public function attendeeIds(CalendarEvent $event): array;

    public function delete(CalendarEvent $event): void;

    /**
     * Takes this member off this household's events: ones they were the sole
     * attendee of are deleted, the rest just lose them. Other households' events are untouched.
     */
    public function removeAttendeeFrom(User $member, Household $household): void;
}
