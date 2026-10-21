<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction
{
    /** @return string One of the `Password::` status constants. */
    public function handle(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }
}
