<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChoreStatusEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Chore> */
class ChoreFactory extends Factory
{
    protected $model = Chore::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'assigned_to' => User::factory(),
            'name' => $this->faker->sentence(3),
            'status' => ChoreStatusEnum::Today,
            'position' => 0,
        ];
    }
}
