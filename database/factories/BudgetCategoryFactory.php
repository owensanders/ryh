<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Palette;
use App\Models\BudgetCategory;
use App\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BudgetCategory> */
class BudgetCategoryFactory extends Factory
{
    protected $model = BudgetCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'label' => $this->faker->words(2, true),
            'icon' => null,
            'colour' => Palette::Coral,
            'budgeted_pence' => 50000,
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
