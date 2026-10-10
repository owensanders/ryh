<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Enums\PendingReasonEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class JoinHouseholdUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
        private readonly UserRepositoryInterface $users,
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

        if ($household->isFull()) {
            throw ValidationException::withMessages(['code' => 'That household is full, so it can\'t take any more members.']);
        }

        $colours = PaletteEnum::cases();
        $colour = $colours[$this->households->members($household)->count() % count($colours)];

        $this->users->update($user, [
            'current_household_id' => $household->id,
            'initials' => mb_strtoupper(mb_substr(trim($user->name), 0, 2)),
            'colour' => $colour,
        ]);

        $user->households()->attach($household->id, [
            'role' => HouseholdRoleEnum::Adult,
            'pending' => true,
            'pending_reason' => PendingReasonEnum::Requested,
        ]);

        return $household;
    }
}
