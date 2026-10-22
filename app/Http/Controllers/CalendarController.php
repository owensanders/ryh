<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Calendar\AddCalendarEventAction;
use App\Actions\Calendar\DeleteCalendarEventAction;
use App\Actions\Calendar\UpdateCalendarEventAction;
use App\Http\Requests\CalendarEventRequest;
use App\Http\Requests\IndexCalendarRequest;
use App\Models\CalendarEvent;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Calendar\CalendarViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(IndexCalendarRequest $request, CalendarViewModel $getCalendarMonth): Response
    {
        $month = $request->validated('month');

        return Inertia::render('Calendar', [
            'calendar' => $getCalendarMonth->toData(
                $this->household($request),
                $this->user($request),
                // `!` resets the unspecified day/time — without it PHP fills the day from
                // today, so on the 31st "2026-09" would roll over into October.
                $month !== null ? CarbonImmutable::createFromFormat('!Y-m', $month) ?? $this->household($request)->now() : $this->household($request)->now(),
            ),
        ]);
    }

    public function store(CalendarEventRequest $request, AddCalendarEventAction $add): RedirectResponse
    {
        $event = $add->handle(
            $this->household($request),
            $request->validated(),
            $this->user($request),
        );

        return back()->with('toast', "“{$event->title}” added");
    }

    public function update(CalendarEventRequest $request, CalendarEvent $event, UpdateCalendarEventAction $update): RedirectResponse
    {
        $updated = $update->handle($this->household($request), $event, $request->validated(), $this->user($request));

        return back()->with('toast', "“{$updated->title}” updated");
    }

    public function destroy(Request $request, CalendarEvent $event, DeleteCalendarEventAction $delete): RedirectResponse
    {
        $title = $event->title;
        $delete->handle($this->household($request), $event, $this->user($request));

        return back()->with('toast', "“{$title}” deleted");
    }
}
