<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PaletteEnum;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JoinHouseholdFromInviteUseCase
{
    public function __construct(
        private readonly HouseholdRepositoryInterface $households,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Invites to people without an account don't hold a place until they sign up,
     * so the household may have filled up since — false means they weren't added.
     */
    /**
     * @param  int|null  $guardianId  For a Teen or Child, the adult who confirmed
     *                                they're a parent or guardian when inviting them.
     */
    public function execute(User $user, Household $household, HouseholdRoleEnum $role, ?int $guardianId = null, ?\DateTimeInterface $guardianConfirmedAt = null): bool
    {
        // Under the household's lock, so two people can't both take its last place.
        return DB::transaction(function () use ($user, $household, $role, $guardianId, $guardianConfirmedAt): bool {
            $household = $this->households->lockForUpdate($household);

            if ($household->isFull()) {
                return false;
            }

            $colours = PaletteEnum::cases();
            $colour = $colours[$this->households->members($household)->count() % count($colours)];

            $this->users->update($user, [
                'current_household_id' => $household->id,
                'initials' => mb_strtoupper(mb_substr(trim($user->name), 0, 2)),
                'colour' => $colour,
            ]);

            $user->households()->attach($household->id, [
                'role' => $role,
                'pending' => false,
                'pending_reason' => null,
                ...HouseholdUser::guardianAttributes($role, $guardianId, $guardianConfirmedAt),
            ]);

            return true;
        });
    }
}
