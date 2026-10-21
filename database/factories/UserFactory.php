<?php

namespace Database\Factories;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'initials' => fake()->unique()->lexify('??'),
            'colour' => fake()->randomElement(PaletteEnum::cases()),
            'status_line' => fake()->sentence(3),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * `household_id`/`role`/`pending`/`pending_reason` aren't real User columns
     * any more — they describe the membership row to attach after creation, so
     * every existing `User::factory()->create(['household_id' => ..., 'role' => ...])`
     * call across the test suite keeps working unchanged.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        if ($this->count !== null && $this->count !== 1) {
            return parent::create($attributes, $parent);
        }

        $membershipKeys = ['household_id', 'role', 'pending', 'pending_reason'];
        $membership = collect($attributes)->only($membershipKeys);
        $attributes = collect($attributes)->except($membershipKeys)->all();

        /** @var User $user */
        $user = parent::create($attributes, $parent);

        $householdId = $membership->has('household_id')
            ? $membership->get('household_id')
            : Household::factory()->create()->id;

        if ($householdId !== null) {
            $user->households()->attach($householdId, [
                'role' => $membership->get('role', HouseholdRoleEnum::Adult),
                'pending' => $membership->get('pending', false),
                'pending_reason' => $membership->get('pending_reason'),
            ]);
            $user->forceFill(['last_household_id' => $householdId])->save();
        }

        return $user;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
