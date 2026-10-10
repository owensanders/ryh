<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEventRequest;
use App\Http\Requests\IndexCalendarRequest;
use App\Models\CalendarEvent;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Calendar\AddCalendarEventUseCase;
use App\UseCases\Calendar\DeleteCalendarEventUseCase;
use App\UseCases\Calendar\GetCalendarMonthUseCase;
use App\UseCases\Calendar\UpdateCalendarEventUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(IndexCalendarRequest $request, GetCalendarMonthUseCase $getCalendarMonth): Response
    {
        $month = $request->validated('month');

        return Inertia::render('Calendar', [
            'calendar' => $getCalendarMonth->execute(
                $this->household($request),
                // `!` resets the unspecified day/time — without it PHP fills the day from
                // today, so on the 31st "2026-09" would roll over into October.
                $month !== null ? CarbonImmutable::createFromFormat('!Y-m', $month) ?? CarbonImmutable::now() : CarbonImmutable::now(),
            ),
        ]);
    }

    public function store(CalendarEventRequest $request, AddCalendarEventUseCase $add): RedirectResponse
    {
        $event = $add->execute(
            $this->household($request),
            $request->eventAttributes(),
            $request->attendeeIds(),
            $this->user($request),
        );

        return back()->with('toast', "“{$event->title}” added");
    }

    public function update(CalendarEventRequest $request, CalendarEvent $event, UpdateCalendarEventUseCase $update): RedirectResponse
    {
        $updated = $update->execute($event, $request->eventAttributes(), $request->attendeeIds(), $this->user($request));

        return back()->with('toast', "“{$updated->title}” updated");
    }

    public function destroy(Request $request, CalendarEvent $event, DeleteCalendarEventUseCase $delete): RedirectResponse
    {
        $title = $event->title;
        $delete->execute($event, $this->user($request));

        return back()->with('toast', "“{$title}” deleted");
    }
}
