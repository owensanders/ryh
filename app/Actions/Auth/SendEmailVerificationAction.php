<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

class SendEmailVerificationAction
{
    /** False when the address is already verified, so no notification was sent. */
    public function handle(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }
}
