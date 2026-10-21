<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function handle(User $user, string $password): User
    {
        $user->forceFill(['password' => Hash::make($password)])->save();

        return $user->refresh();
    }
}
