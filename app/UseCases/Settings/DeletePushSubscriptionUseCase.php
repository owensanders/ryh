<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

class DeletePushSubscriptionUseCase
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function execute(User $user, string $endpoint): void
    {
        $this->users->deletePushSubscription($user, $endpoint);
    }
}
