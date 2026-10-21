<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Verified;

class VerifyEmailUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    /** False when the address was already verified, so nothing changed. */
    public function execute(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        if ($this->users->markEmailAsVerified($user)) {
            event(new Verified($user));
        }

        return true;
    }
}
