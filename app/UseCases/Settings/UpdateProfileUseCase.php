<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Data\Input\ProfileInputData;
use App\Models\User;
use App\Repositories\UserRepository;

class UpdateProfileUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(User $user, ProfileInputData $profile): User
    {
        // Changing the address makes the old verification meaningless.
        return $this->users->updateProfile($user, $profile, unverifyEmail: $profile->email !== $user->email);
    }
}
