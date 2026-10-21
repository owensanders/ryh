<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(User $user, string $password): User
    {
        return $this->users->updatePassword($user, Hash::make($password));
    }
}
