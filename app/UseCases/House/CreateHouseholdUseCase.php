<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateHouseholdUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
        private readonly UserRepositoryInterface $users,
        private readonly InviteMemberUseCase $inviteMember,
    ) {}

    /**
     * `size` is collected for the wizard's copy but has nowhere to live yet —
     * ponytail: not persisted, add a column when a feature actually reads it.
     *
     * @param  array{name: string, size?: string|null, invites?: array<int, array{email: string, role: string}>}  $attributes
     */
    public function execute(User $owner, array $attributes): Household
    {
        // The household, its owner's membership and their claimed trial stand
        // or fall together — a household with no owner would strand them
        // with their one free trial spent.
        $household = DB::transaction(function () use ($owner, $attributes): Household {
            // One free month per person — otherwise a lapsed owner could just
            // start a new household to reset the clock.
            $household = $this->households->create([
                'name' => trim($attributes['name']),
                'trial_ends_at' => $owner->trial_claimed_at === null ? now()->addDays(config('plans.trial_days')) : null,
            ]);

            $this->users->update($owner, [
                'current_household_id' => $household->id,
                'trial_claimed_at' => $owner->trial_claimed_at ?? now(),
                'initials' => mb_strtoupper(mb_substr(trim($owner->name), 0, 2)),
                'colour' => PaletteEnum::cases()[0],
            ]);

            $owner->households()->attach($household->id, ['role' => HouseholdRoleEnum::Owner, 'pending' => false]);

            return $household;
        });

        foreach ($attributes['invites'] ?? [] as $invite) {
            $this->inviteMember->execute($household, [
                'email' => $invite['email'],
                'role' => $invite['role'],
            ], invitedBy: $owner);
        }

        return $household;
    }
}
