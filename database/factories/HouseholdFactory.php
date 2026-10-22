<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Household> */
class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => $this->faker->lastName().' household',
            'location' => $this->faker->city(),
            'trial_ends_at' => now()->addDays(30),
        ];
    }
}
