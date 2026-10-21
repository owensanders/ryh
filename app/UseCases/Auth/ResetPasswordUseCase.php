<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    /**
     * @param  array<string, string>  $credentials  email, token and the new password
     *
     * @return string One of the `Password::` status constants.
     */
    public function execute(array $credentials, string $password): string
    {
        $status = Password::reset($credentials, function (User $user) use ($password): void {
            $this->users->setPassword($user, Hash::make($password));

            event(new PasswordReset($user));
        });

        return is_string($status) ? $status : (string) $status;
    }
}
