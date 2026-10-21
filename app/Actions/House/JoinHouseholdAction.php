<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Enums\PendingReasonEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JoinHouseholdAction
{
    /** @throws ValidationException */
    public function handle(User $user, string $joinCode): Household
    {
        $household = Household::query()->where('join_code', mb_strtoupper(trim($joinCode)))->first();

        if ($household === null) {
            throw ValidationException::withMessages(['code' => 'That code doesn\'t match a household.']);
        }

        if (! $household->join_code_enabled) {
            throw ValidationException::withMessages(['code' => 'This household isn\'t accepting join requests right now.']);
        }

        // Under the household's lock, so two people can't both take its last place.
        return DB::transaction(function () use ($user, $household): Household {
            $household = $household->lockRow();

            if ($household->isFull()) {
                throw ValidationException::withMessages(['code' => 'That household is full, so it can\'t take any more members.']);
            }

            $colours = PaletteEnum::cases();
            $user->moveInto($household, $colours[$household->members()->count() % count($colours)]);

            $household->addMember($user, HouseholdRoleEnum::Adult, pending: true, pendingReason: PendingReasonEnum::Requested);

            return $household;
        });
    }
}
