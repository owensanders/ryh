<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Inputs\HouseholdSetupInput;
use App\Models\Household;
use App\Models\User;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

class CreateHouseholdAction
{
    private const string DEFAULT_TIMEZONE = 'Europe/London';

    public function __construct(
        private readonly InviteMemberAction $inviteMember,
    ) {}

    public function handle(User $owner, HouseholdSetupInput $input): Household
    {
        // The household, its owner's membership and their claimed trial stand
        // or fall together — a household with no owner would strand them
        // with their one free trial spent.
        $household = DB::transaction(function () use ($owner, $input): Household {
            // One free month per person — otherwise a lapsed owner could just
            // start a new household to reset the clock.
            $household = Household::create([
                'name' => $input->name,
                'timezone' => self::knownTimezone($input->timezone),
                'trial_ends_at' => $owner->trial_claimed_at === null ? now()->addDays(config('plans.trial_days')) : null,
            ]);

            $owner->moveInto($household, PaletteEnum::cases()[0]);
            $owner->update(['trial_claimed_at' => $owner->trial_claimed_at ?? now()]);

            $household->addMember($owner, HouseholdRoleEnum::Owner);

            return $household;
        });

        foreach ($input->invites as $invite) {
            $this->inviteMember->handle($household, $invite, invitedBy: $owner);
        }

        return $household;
    }

    /** A browser's time zone, if PHP knows it; otherwise the UK, where most households are. */
    private static function knownTimezone(?string $timezone): string
    {
        return $timezone !== null && in_array($timezone, timezone_identifiers_list(DateTimeZone::ALL_WITH_BC), true)
            ? $timezone
            : self::DEFAULT_TIMEZONE;
    }
}
