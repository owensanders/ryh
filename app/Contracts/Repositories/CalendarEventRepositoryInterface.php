<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface CalendarEventRepositoryInterface
{
    /** @return Collection<int, CalendarEvent> */
    public function between(Household $household, CarbonImmutable $from, CarbonImmutable $to): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $attendeeIds
     */
    public function create(Household $household, array $attributes, array $attendeeIds): CalendarEvent;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $attendeeIds
     */
    public function update(CalendarEvent $event, array $attributes, array $attendeeIds): CalendarEvent;

    public function delete(CalendarEvent $event): void;

    /**
     * Takes this member off this household's events: ones they were the sole
     * attendee of are deleted, the rest just lose them. Other households' events are untouched.
     */
    public function removeAttendeeFrom(User $member, Household $household): void;
}
