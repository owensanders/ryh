<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ConfirmPasswordAction
{
    /** True when the password belongs to the user, recording the confirmation. */
    public function handle(User $user, string $password): bool
    {
        if (! Auth::guard('web')->validate(['email' => $user->email, 'password' => $password])) {
            return false;
        }

        session()->put('auth.password_confirmed_at', time());

        return true;
    }
}
