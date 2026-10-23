<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HouseholdRoleEnum;
use App\Enums\MealSlotEnum;
use App\Models\CalendarEvent;
use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A household's days turn over at its own midnight. At 23:30 UTC on 22
 * October it's already 12:30 on the 23rd in Auckland, so an Auckland
 * household is on the 23rd while the server's clock is still on the 22nd.
 */
class HouseholdTimezoneTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-22 23:30:00', 'UTC'));
        $this->owner = User::factory()->create(['role' => HouseholdRoleEnum::Owner]);
        Household::where('id', $this->owner->household_id)->update(['timezone' => 'Pacific/Auckland']);
    }

    #[Test]
    public function aHouseholdsTodayIsItsOwnDate(): void
    {
        $household = $this->owner->lastHousehold()->firstOrFail();

        $this->assertSame('2026-10-23', $household->today()->toDateString());
        $this->assertSame('2026-10-23 12:30:00', $household->now()->toDateTimeString());

        $household->update(['timezone' => 'UTC']);
        $this->assertSame('2026-10-22', $household->today()->toDateString());
    }

    #[Test]
    public function theCalendarAndPlannerShowTheHouseholdsToday(): void
    {
        $this->actingAs($this->owner)->get($this->hh('/calendar'))
            ->assertInertia(fn ($page) => $page->where('calendar.todayDate', '2026-10-23'));

        $this->actingAs($this->owner)->get($this->hh('/meals'))
            ->assertInertia(function ($page) {
                /** @var list<array{date: string, isToday: bool}> $days */
                $days = $page->toArray()['props']['days'];

                $this->assertSame(['2026-10-23'], array_column(array_filter($days, fn (array $day) => $day['isToday']), 'date'));
            });
    }

    #[Test]
    public function tonightsDinnerIsTheHouseholdsTonight(): void
    {
        $recipe = Recipe::factory()->create(['household_id' => $this->owner->household_id, 'name' => 'Pavlova']);
        PlannedMeal::factory()->create([
            'household_id' => $this->owner->household_id,
            'recipe_id' => $recipe->id,
            'planned_on' => '2026-10-23',
            'slot' => MealSlotEnum::Dinner,
        ]);

        $this->actingAs($this->owner)->get($this->hh('/dashboard'))
            ->assertInertia(fn ($page) => $page->where('dashboard.tonight.name', 'Pavlova'));
    }

    #[Test]
    public function aNewEventCanStartOnTheHouseholdsTodayButNotBefore(): void
    {
        $this->actingAs($this->owner)
            ->post($this->hh('/calendar/events'), ['title' => 'Picnic', 'starts_at' => '2026-10-23T18:00'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post($this->hh('/calendar/events'), ['title' => 'Too late', 'starts_at' => '2026-10-22T18:00'])
            ->assertSessionHasErrors(['starts_at' => 'The event can\'t be scheduled in the past.']);
    }

    #[Test]
    public function anEventStillRunningCanBeChangedButNotMovedIntoThePast(): void
    {
        // Started yesterday, ends tomorrow, by the household's clock.
        $event = CalendarEvent::factory()->create([
            'household_id' => $this->owner->household_id,
            'title' => 'Camping',
            'starts_at' => CarbonImmutable::parse('2026-10-22 09:00', 'Pacific/Auckland'),
            'ends_at' => CarbonImmutable::parse('2026-10-24 17:00', 'Pacific/Auckland'),
        ]);

        $this->assertSame([false], $this->hasEndedFlags());

        $this->actingAs($this->owner)
            ->patch($this->hh("/calendar/events/{$event->id}"), [
                'title' => 'Camping by the lake',
                'starts_at' => '2026-10-22T09:00',
                'ends_at' => '2026-10-24T17:00',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('Camping by the lake', $event->refresh()->title);

        $this->actingAs($this->owner)
            ->patch($this->hh("/calendar/events/{$event->id}"), [
                'title' => 'Camping by the lake',
                'starts_at' => '2026-10-21T09:00',
                'ends_at' => '2026-10-24T17:00',
            ])
            ->assertSessionHasErrors(['starts_at' => 'The event can\'t be scheduled in the past.']);
    }

    #[Test]
    public function anEventThatIsOverCantBeChanged(): void
    {
        // Over by the household's clock (12:30 on the 23rd), though by UTC's it ended only 30 minutes ago.
        $event = CalendarEvent::factory()->create([
            'household_id' => $this->owner->household_id,
            'title' => 'Breakfast',
            'starts_at' => CarbonImmutable::parse('2026-10-23 08:00', 'Pacific/Auckland'),
            'ends_at' => CarbonImmutable::parse('2026-10-23 09:00', 'Pacific/Auckland'),
        ]);

        $this->assertSame([true], $this->hasEndedFlags());

        $this->actingAs($this->owner)
            ->patch($this->hh("/calendar/events/{$event->id}"), [
                'title' => 'Brunch',
                'starts_at' => '2026-10-23T08:00',
                'ends_at' => '2026-10-23T09:00',
            ])
            ->assertSessionHasErrors(['starts_at' => 'This event has already happened, so it can\'t be changed.']);

        $this->assertSame('Breakfast', $event->refresh()->title);
    }

    #[Test]
    public function aNewHouseholdTakesItsCreatorsTimezoneOrTheUks(): void
    {
        $tokyo = User::factory()->create(['household_id' => null]);
        $this->actingAs($tokyo)->post('/household/setup', ['name' => 'The Satos', 'timezone' => 'Asia/Tokyo'])->assertSessionHasNoErrors();
        $this->assertSame('Asia/Tokyo', $tokyo->refresh()->lastHousehold?->timezone);

        $unknown = User::factory()->create(['household_id' => null]);
        $this->actingAs($unknown)->post('/household/setup', ['name' => 'The Nowheres', 'timezone' => 'Mars/Olympus_Mons'])->assertSessionHasNoErrors();
        $this->assertSame('Europe/London', $unknown->refresh()->lastHousehold?->timezone);
    }

    #[Test]
    public function onlyTheOwnerCanChangeTheTimezoneAndOnlyToARealOne(): void
    {
        $adult = User::factory()->create(['household_id' => $this->owner->household_id, 'role' => HouseholdRoleEnum::Adult]);

        $this->actingAs($adult)->patch($this->hh('/house/timezone'), ['timezone' => 'Europe/Paris'])->assertForbidden();

        $this->actingAs($this->owner)
            ->patch($this->hh('/house/timezone'), ['timezone' => 'Not/AZone'])
            ->assertSessionHasErrors(['timezone' => 'Pick a time zone from the list.']);

        $this->actingAs($this->owner)
            ->patch($this->hh('/house/timezone'), ['timezone' => 'Europe/Paris'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('toast', 'Time zone updated');

        $this->assertSame('Europe/Paris', $this->owner->lastHousehold()->firstOrFail()->timezone);
    }

    /**
     * Each distinct event's `hasEnded` on this month's calendar.
     *
     * @return list<bool>
     */
    private function hasEndedFlags(): array
    {
        $flags = [];

        $this->actingAs($this->owner)->get($this->hh('/calendar'))->assertInertia(function ($page) use (&$flags): void {
            /** @var list<array{events: list<array{id: int, hasEnded: bool}>}> $days */
            $days = $page->toArray()['props']['calendar']['days'];

            foreach ($days as $day) {
                foreach ($day['events'] as $event) {
                    $flags[$event['id']] = $event['hasEnded'];
                }
            }
        });

        return array_values($flags);
    }
}
