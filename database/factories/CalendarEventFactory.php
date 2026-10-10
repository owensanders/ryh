<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaletteEnum;
use App\Models\CalendarEvent;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<CalendarEvent> */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'title' => $this->faker->sentence(3),
            'starts_at' => Carbon::today()->setTime(9, 0),
            'ends_at' => null,
            'is_all_day' => false,
            'location' => null,
            'notes' => null,
            'colour' => PaletteEnum::Mint,
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn () => ['is_all_day' => true, 'starts_at' => Carbon::today()->startOfDay()]);
    }
}
