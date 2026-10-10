<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\MembershipRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Enums\PendingReasonEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JoinHouseholdUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
        private readonly UserRepositoryInterface $users,
        private readonly MembershipRepositoryInterface $memberships,
    ) {}

    /** @throws ValidationException */
    public function execute(User $user, string $joinCode): Household
    {
        $household = $this->households->findByJoinCode(mb_strtoupper(trim($joinCode)));

        if ($household === null) {
            throw ValidationException::withMessages(['code' => 'That code doesn\'t match a household.']);
        }

        if (! $household->join_code_enabled) {
            throw ValidationException::withMessages(['code' => 'This household isn\'t accepting join requests right now.']);
        }

        // Under the household's lock, so two people can't both take its last place.
        return DB::transaction(function () use ($user, $household): Household {
            $household = $this->households->lockForUpdate($household);

            if ($this->memberships->isFull($household)) {
                throw ValidationException::withMessages(['code' => 'That household is full, so it can\'t take any more members.']);
            }

            $colours = PaletteEnum::cases();
            $this->users->moveIntoHousehold($user, $household, $colours[$this->memberships->count($household) % count($colours)]);

            $this->memberships->add($user, $household, HouseholdRoleEnum::Adult, pending: true, pendingReason: PendingReasonEnum::Requested);

            return $household;
        });
    }
}
