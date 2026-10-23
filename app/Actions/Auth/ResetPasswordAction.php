<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordAction
{
    /** @return string One of the `Password::` status constants. */
    public function handle(string $email, string $token, string $password): string
    {
        $credentials = ['email' => $email, 'token' => $token, 'password' => $password];

        $status = Password::reset($credentials, function (User $user) use ($password): void {
            // A reset signs out every "remember me" session too.
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();

            event(new PasswordReset($user));
        });

        return is_string($status) ? $status : (string) $status;
    }
}
