<?php

declare(strict_types=1);

namespace App\UseCases\House;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\HouseholdRole;
use App\Enums\PendingReason;
use App\Mail\HouseholdInviteMail;
use App\Models\Household;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class InviteMemberUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /** @param  array{email: string, role: string}  $attributes */
    public function execute(Household $household, array $attributes): void
    {
        $email = $attributes['email'];

        if ($household->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This person is already part of the household.']);
        }

        if ($household->isFull()) {
            throw ValidationException::withMessages([
                'email' => 'Your household is full — it can have up to '.config('plans.max_members').' members, including pending invites.',
            ]);
        }

        $existing = $this->users->findByEmail($email);

        if ($existing !== null) {
            $existing->households()->attach($household->id, [
                'role' => HouseholdRole::from($attributes['role']),
                'pending' => true,
                'pending_reason' => PendingReason::Invited,
            ]);

            $actionUrl = URL::temporarySignedRoute('household-invites.accept', now()->addDays(7), [
                'household' => $household->id,
                'member' => $existing->id,
            ]);

            Mail::to($email)->queue(new HouseholdInviteMail($household, $actionUrl, hasAccount: true));

            return;
        }

        $actionUrl = URL::temporarySignedRoute('register', now()->addDays(7), [
            'invite_household' => $household->id,
            'invite_role' => $attributes['role'],
            'email' => $email,
        ]);

        Mail::to($email)->queue(new HouseholdInviteMail($household, $actionUrl, hasAccount: false));
    }
}
