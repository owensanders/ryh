<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(User $user, string $password): User
    {
        return $this->users->updatePassword($user, Hash::make($password));
    }
}
