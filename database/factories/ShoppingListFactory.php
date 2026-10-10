<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\ShoppingList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ShoppingList> */
class ShoppingListFactory extends Factory
{
    protected $model = ShoppingList::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'household_id' => Household::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'colour' => $this->faker->randomElement(PaletteEnum::cases()),
            'position' => 0,
        ];
    }
}
