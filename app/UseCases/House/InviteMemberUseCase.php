<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\HouseholdRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRoleEnum;
use App\Enums\PendingReasonEnum;
use App\Mail\HouseholdInviteMail;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class InviteMemberUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly HouseholdRepositoryInterface $households,
    ) {}

    /**
     * Inviting someone as a Teen or Child is the inviter confirming they're the
     * child's parent or guardian (or have their permission) — the caller must
     * have had them tick that box first. It's recorded on the membership.
     *
     * @param  array{email: string, role: string}  $attributes
     */
    public function execute(Household $household, array $attributes, User $invitedBy): void
    {
        $email = $attributes['email'];
        $role = HouseholdRoleEnum::from($attributes['role']);

        $existing = $this->users->findByEmail($email);

        // An invite to someone with an account takes a place in the household
        // straight away, so the cap check and the new membership are made under
        // the household's lock: two invites at once can't both take the last place.
        DB::transaction(function () use ($household, $email, $role, $existing, $invitedBy): void {
            $household = $this->households->lockForUpdate($household);

            if ($household->members()->where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'This person is already part of the household.']);
            }

            if ($household->isFull()) {
                throw ValidationException::withMessages([
                    'email' => 'Your household is full — it can have up to '.config('plans.max_members').' members, including pending invites.',
                ]);
            }

            $existing?->households()->attach($household->id, [
                'role' => $role,
                'pending' => true,
                'pending_reason' => PendingReasonEnum::Invited,
                ...HouseholdUser::guardianAttributes($role, $invitedBy->id),
            ]);
        });

        if ($existing !== null) {
            $actionUrl = URL::temporarySignedRoute('household-invites.accept', now()->addDays(7), [
                'household' => $household->id,
                'member' => $existing->id,
            ]);

            Mail::to($email)->queue(new HouseholdInviteMail($household, $actionUrl, hasAccount: true));

            return;
        }

        // No membership exists until they register, so the signed link carries
        // the guardian's confirmation through to it.
        $actionUrl = URL::temporarySignedRoute('register', now()->addDays(7), [
            'invite_household' => $household->id,
            'invite_role' => $role->value,
            'email' => $email,
            ...($role->isForMinors() ? ['invite_guardian' => $invitedBy->id, 'invite_guardian_at' => now()->getTimestamp()] : []),
        ]);

        Mail::to($email)->queue(new HouseholdInviteMail($household, $actionUrl, hasAccount: false));
    }
}
