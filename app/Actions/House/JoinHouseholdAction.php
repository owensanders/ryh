<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Enums\PendingReasonEnum;
use App\Exceptions\MembershipException;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JoinHouseholdAction
{
    /** @throws MembershipException */
    public function handle(User $user, string $joinCode): Household
    {
        $household = Household::query()->where('join_code', mb_strtoupper(trim($joinCode)))->first();

        if ($household === null) {
            throw MembershipException::unknownJoinCode();
        }

        if (! $household->join_code_enabled) {
            throw MembershipException::notTakingJoinRequests();
        }

        // Under the household's lock, so two people can't both take its last place.
        return DB::transaction(function () use ($user, $household): Household {
            $household = $household->lockRow();

            if ($household->isFull()) {
                throw MembershipException::fullForJoining();
            }

            $colours = PaletteEnum::cases();
            $user->moveInto($household, $colours[$household->members()->count() % count($colours)]);

            $household->addMember($user, HouseholdRoleEnum::Adult, pending: true, pendingReason: PendingReasonEnum::Requested);

            return $household;
        });
    }
}
