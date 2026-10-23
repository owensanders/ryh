<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaletteEnum;
use App\Models\CalendarEvent;
use App\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalendarEvent> */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    /**
     * Times are UTC moments, as stored: 09:00 today on the clock of a
     * household with the default time zone (Europe/London).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'title' => $this->faker->sentence(3),
            'starts_at' => CarbonImmutable::today('Europe/London')->setTime(9, 0)->utc(),
            'ends_at' => null,
            'is_all_day' => false,
            'location' => null,
            'notes' => null,
            'colour' => PaletteEnum::Mint,
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn () => ['is_all_day' => true, 'starts_at' => CarbonImmutable::today('Europe/London')->utc()]);
    }
}
