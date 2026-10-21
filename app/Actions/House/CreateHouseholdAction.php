<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateHouseholdAction
{
    public function __construct(
        private readonly InviteMemberAction $inviteMember,
    ) {}

    /** @param  array<string, mixed>  $data  HouseholdSetupRequest's validated input: name, invites (each email and role). */
    public function handle(User $owner, array $data): Household
    {
        // The household, its owner's membership and their claimed trial stand
        // or fall together — a household with no owner would strand them
        // with their one free trial spent.
        $household = DB::transaction(function () use ($owner, $data): Household {
            // One free month per person — otherwise a lapsed owner could just
            // start a new household to reset the clock.
            $household = Household::create([
                'name' => $data['name'],
                'trial_ends_at' => $owner->trial_claimed_at === null ? now()->addDays(config('plans.trial_days')) : null,
            ]);

            $owner->moveInto($household, PaletteEnum::cases()[0]);
            $owner->update(['trial_claimed_at' => $owner->trial_claimed_at ?? now()]);

            $household->addMember($owner, HouseholdRoleEnum::Owner);

            return $household;
        });

        foreach ($data['invites'] ?? [] as $invite) {
            $this->inviteMember->handle($household, $invite, invitedBy: $owner);
        }

        return $household;
    }
}
