<?php

declare(strict_types=1);

namespace App\UseCases\Settings;

use App\Models\User;
use App\Repositories\UserRepository;

class DeletePushSubscriptionUseCase
{
    public function __construct(private readonly UserRepository $users) {}

    public function execute(User $user, string $endpoint): void
    {
        $this->users->deletePushSubscription($user, $endpoint);
    }
}
