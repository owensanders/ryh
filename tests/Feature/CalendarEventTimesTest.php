<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Event times are stored as UTC moments. A household types and reads them
 * on its own clock, so the same "18:00" is a different moment in Auckland
 * and London, and moving a household to another zone keeps its events at
 * the same moment, showing a different clock time.
 */
class CalendarEventTimesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-01 12:00:00', 'UTC'));
    }

    #[Test]
    public function theSameClockTimeIsADifferentMomentInEachHousehold(): void
    {
        $london = $this->ownerIn('Europe/London');
        $auckland = $this->ownerIn('Pacific/Auckland');

        foreach ([$london, $auckland] as $owner) {
            $this->actingAs($owner)
                ->post($this->hh('/calendar/events'), ['title' => 'Dinner', 'starts_at' => '2026-10-23T18:00', 'ends_at' => '2026-10-23T19:30'])
                ->assertSessionHasNoErrors();
        }

        // British Summer Time is UTC+1; New Zealand Daylight Time is UTC+13.
        $this->assertSame('2026-10-23 17:00:00', $this->rawStart($london));
        $this->assertSame('2026-10-23 05:00:00', $this->rawStart($auckland));

        foreach ([$london, $auckland] as $owner) {
            $event = $this->calendarEvent($owner, '2026-10', '2026-10-23');
            $this->assertSame('18:00–19:30', $event['time']);
            $this->assertSame('2026-10-23T18:00', $event['startsAt']);
        }
    }

    #[Test]
    public function anEveningEventInAucklandIsOnItsOwnDateNotUtcs(): void
    {
        $auckland = $this->ownerIn('Pacific/Auckland');

        $this->actingAs($auckland)
            ->post($this->hh('/calendar/events'), ['title' => 'Breakfast', 'starts_at' => '2026-10-23T08:00'])
            ->assertSessionHasNoErrors();

        // 08:00 on the 23rd in Auckland is 19:00 on the 22nd in UTC.
        $this->assertSame('2026-10-22 19:00:00', $this->rawStart($auckland));
        $this->assertSame('08:00', $this->calendarEvent($auckland, '2026-10', '2026-10-23')['time']);
        $this->assertSame([], $this->eventsOn($auckland, '2026-10', '2026-10-22'));
    }

    #[Test]
    public function aTimeThatHappensTwiceWhenTheClocksGoBackIsTheFirst(): void
    {
        $london = $this->ownerIn('Europe/London');

        // On 25 October 2026 the UK's clocks go back at 02:00 BST, so 01:30 happens twice.
        $this->actingAs($london)
            ->post($this->hh('/calendar/events'), ['title' => 'Night feed', 'starts_at' => '2026-10-25T01:30'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-25 00:30:00', $this->rawStart($london));
        $this->assertSame('2026-10-25T01:30', $this->calendarEvent($london, '2026-10', '2026-10-25')['startsAt']);
    }

    #[Test]
    public function aTimeThatNeverHappensWhenTheClocksGoForwardLandsAnHourLater(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-03-01 12:00:00', 'UTC'));
        $london = $this->ownerIn('Europe/London');

        // On 28 March 2027 the UK's clocks go forward at 01:00 GMT, so 01:30 never happens.
        $this->actingAs($london)
            ->post($this->hh('/calendar/events'), ['title' => 'Early start', 'starts_at' => '2027-03-28T01:30'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2027-03-28 01:30:00', $this->rawStart($london));
        $this->assertSame('02:30', $this->calendarEvent($london, '2027-03', '2027-03-28')['time']);
    }

    #[Test]
    public function changingTheTimeZoneKeepsEventsAtTheSameMoment(): void
    {
        $owner = $this->ownerIn('Europe/London');

        $this->actingAs($owner)
            ->post($this->hh('/calendar/events'), ['title' => 'Call with Mum', 'starts_at' => '2026-10-23T18:00'])
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->patch($this->hh('/house/timezone'), ['timezone' => 'Europe/Paris'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-23 17:00:00', $this->rawStart($owner));
        $this->assertSame('19:00', $this->calendarEvent($owner, '2026-10', '2026-10-23')['time']);
    }

    #[Test]
    public function aTimeInAnyZoneIsStoredAsUtc(): void
    {
        $owner = $this->ownerIn('Pacific/Auckland');

        CalendarEvent::factory()->create([
            'household_id' => $owner->last_household_id,
            'starts_at' => CarbonImmutable::parse('2026-10-23 18:00', 'Pacific/Auckland'),
        ]);

        $this->assertSame('2026-10-23 05:00:00', $this->rawStart($owner));
    }

    #[Test]
    public function theMigrationConvertsClockTimesToUtcAndBack(): void
    {
        $london = $this->ownerIn('Europe/London');
        $auckland = $this->ownerIn('Pacific/Auckland');
        $rows = [
            [$london, '2026-10-23 18:00:00', '2026-10-23 19:30:00'],
            [$london, '2026-10-25 01:30:00', null],
            [$auckland, '2026-10-23 08:00:00', null],
        ];

        foreach ($rows as [$owner, $startsAt, $endsAt]) {
            DB::table('calendar_events')->insert([
                'household_id' => $owner->last_household_id,
                'title' => 'Before the migration',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'colour' => 'mint',
            ]);
        }

        $migration = require database_path('migrations/2026_10_23_000000_store_calendar_event_times_as_utc.php');
        assert($migration instanceof Migration && method_exists($migration, 'up') && method_exists($migration, 'down'));

        $migration->up();
        $this->assertSame([
            ['2026-10-23 17:00:00', '2026-10-23 18:30:00'],
            ['2026-10-25 00:30:00', null],
            ['2026-10-22 19:00:00', null],
        ], $this->rawTimes());

        $migration->down();
        $this->assertSame(array_map(fn (array $row) => [$row[1], $row[2]], $rows), $this->rawTimes());
    }

    private function ownerIn(string $timezone): User
    {
        $owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        Household::where('id', $owner->last_household_id)->update(['timezone' => $timezone]);

        return $owner;
    }

    private function rawStart(User $owner): string
    {
        return (string) DB::table('calendar_events')->where('household_id', $owner->last_household_id)->value('starts_at');
    }

    /** @return array<int, array{0: string, 1: string|null}> */
    private function rawTimes(): array
    {
        return DB::table('calendar_events')->orderBy('id')->get(['starts_at', 'ends_at'])
            ->map(fn (object $row) => [(string) $row->starts_at, $row->ends_at === null ? null : (string) $row->ends_at])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> The one event on `$date` in the owner's calendar. */
    private function calendarEvent(User $owner, string $month, string $date): array
    {
        $events = $this->eventsOn($owner, $month, $date);
        $this->assertCount(1, $events);

        return $events[0];
    }

    /** @return list<array<string, mixed>> */
    private function eventsOn(User $owner, string $month, string $date): array
    {
        $events = null;

        $this->actingAs($owner)->get($this->hh("/calendar?month={$month}"))->assertInertia(function ($page) use ($date, &$events): void {
            /** @var list<array{date: string, events: list<array<string, mixed>>}> $days */
            $days = $page->toArray()['props']['calendar']['days'];
            $events = collect($days)->firstWhere('date', $date)['events'] ?? [];
        });

        return $events ?? [];
    }
}
