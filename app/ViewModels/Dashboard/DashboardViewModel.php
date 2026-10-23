<?php

declare(strict_types=1);

namespace App\ViewModels\Dashboard;

use App\Data\AgendaEntryData;
use App\Data\AgendaGroupData;
use App\Data\BudgetCategoryData;
use App\Data\BudgetSummaryData;
use App\Data\ChoreData;
use App\Data\ChoreProgressData;
use App\Data\DashboardData;
use App\Data\MemberData;
use App\Data\PlannedMealData;
use App\Data\ShoppingListData;
use App\Enums\ChoreStatusEnum;
use App\Enums\MealSlotEnum;
use App\Models\BudgetCategory;
use App\Models\CalendarEvent;
use App\Models\Chore;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\ShoppingList;
use App\Models\User;
use App\Support\MoneyHelper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardViewModel
{
    /** How many days of the calendar the "Coming up" card shows. */
    private const int AGENDA_DAYS = 3;

    public function toData(Household $household, User $viewer, CarbonImmutable $today): DashboardData
    {
        $chores = $household->chores()->forBoard()->get();
        $todays = $chores->whereIn('status', [ChoreStatusEnum::Today, ChoreStatusEnum::Done]);
        $done = $todays->where('status', ChoreStatusEnum::Done)->count();

        $tonight = $household->plannedMeals()
            ->with(['recipe', 'cook'])
            ->whereDate('planned_on', $today->toDateString())
            ->where('slot', MealSlotEnum::Dinner)
            ->first();
        // A list that actually has items, so the dashboard doesn't spotlight an empty one.
        $list = $household->shoppingLists()->with('items')->get()
            ->first(fn (ShoppingList $list) => $list->items->isNotEmpty());
        $remaining = $todays->count() - $done;

        // One read of the calendar covers both the summary line and the agenda card.
        $events = $household->calendarEvents()->with('attendees')->overlapping(
            $household->momentAt($today->startOfDay()->toDateTimeString()),
            $household->momentAt($today->addDays(self::AGENDA_DAYS - 1)->endOfDay()->toDateTimeString()),
        )->get();

        return new DashboardData(
            firstName: str($viewer->name)->before(' ')->toString(),
            summaryLine: $this->summaryLine(
                $tonight,
                $events->filter(fn (CalendarEvent $event) => $event->startsIn($household->timezone)->toDateString() === $today->toDateString())->count(),
                $remaining,
            ),
            streakDays: $viewer->currentStreak($today),
            family: $household->activeMembers()->get()->map(MemberData::fromModel(...))->values()->all(),
            tonight: $tonight !== null ? PlannedMealData::fromModel($tonight, $viewer) : null,
            shoppingList: $list !== null ? ShoppingListData::fromModel($list, $viewer) : null,
            chores: $todays->map(fn (Chore $chore) => ChoreData::fromModel($chore, $viewer))->values()->all(),
            choreProgress: ChoreProgressData::make($done, $todays->count()),
            agenda: $this->agenda($events, $today, $household->timezone),
            budget: $this->budget($household, $today),
        );
    }

    private function summaryLine(?PlannedMeal $tonight, int $events, int $choresLeft): string
    {
        $parts = [];

        if ($tonight !== null) {
            $parts[] = $tonight->recipe->name.' tonight';
        }

        $parts[] = $events.' '.Str::plural('event', $events);
        $parts[] = $choresLeft.' '.Str::plural('chore', $choresLeft).' left';

        return implode(' · ', $parts);
    }

    /**
     * Groups the next few days of events under Today / Tomorrow / weekday headings.
     *
     * @param  Collection<int, CalendarEvent>  $events
     *
     * @return array<int, AgendaGroupData>
     */
    private function agenda(Collection $events, CarbonImmutable $today, string $timezone): array
    {
        $groups = [];

        for ($offset = 0; $offset < self::AGENDA_DAYS; $offset++) {
            $day = $today->addDays($offset);
            $items = $events
                ->filter(fn (CalendarEvent $event) => $event->startsIn($timezone)->toDateString() === $day->toDateString())
                ->map(fn (CalendarEvent $event) => AgendaEntryData::fromModel($event, $timezone))
                ->values()
                ->all();

            if ($items === []) {
                continue;
            }

            $groups[] = new AgendaGroupData(
                label: match ($offset) {
                    0 => 'Today',
                    1 => 'Tomorrow',
                    default => $day->format('l'),
                },
                items: $items,
            );
        }

        return $groups;
    }

    private function budget(Household $household, CarbonImmutable $today): BudgetSummaryData
    {
        $categories = $household->budgetCategories()->forMonth($today)->get();
        $budgeted = (int) $categories->sum('budgeted_pence');

        return new BudgetSummaryData(
            monthLabel: $today->format('F'),
            budgeted: MoneyHelper::format($budgeted),
            daysLeft: (int) $today->diffInDays($today->endOfMonth(), absolute: true),
            categories: $categories
                ->map(fn (BudgetCategory $category) => BudgetCategoryData::fromModel($category, $budgeted))
                ->values()
                ->all(),
        );
    }
}
