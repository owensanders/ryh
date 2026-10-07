<?php

declare(strict_types=1);

namespace App\UseCases\Auth;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterUserUseCase
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function execute(string $name, string $email, string $password): User
    {
        $user = $this->users->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            // Registration requires ticking the terms box, so record what was agreed to.
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.terms_version'),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}
