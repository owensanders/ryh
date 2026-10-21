<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function handle(string $name, string $email, string $password): User
    {
        // Registration requires ticking the terms box, so record what was agreed to.
        $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password), 'terms_accepted_at' => now(), 'terms_version' => (string) config('legal.terms_version')]);

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}
