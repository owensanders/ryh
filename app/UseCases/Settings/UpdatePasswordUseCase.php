<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordUseCase
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function execute(User $user, string $password): User
    {
        return $this->users->updatePassword($user, Hash::make($password));
    }
}
