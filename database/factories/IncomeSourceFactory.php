<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\IncomeSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IncomeSource> */
class IncomeSourceFactory extends Factory
{
    protected $model = IncomeSource::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'label' => $this->faker->words(2, true),
            'meta' => 'Every 4 weeks',
            'colour' => PaletteEnum::Mint,
            'amount_pence' => 200000,
            'is_recurring' => false,
            'month' => CarbonImmutable::now()->startOfMonth(),
            'position' => 0,
        ];
    }

    /** @return static */
    public function recurring(): self
    {
        return $this->state(['is_recurring' => true]);
    }
}
