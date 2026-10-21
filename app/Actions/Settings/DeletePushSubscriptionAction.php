<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;
use App\Repositories\UserRepository;

class DeletePushSubscriptionAction
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(User $user, string $endpoint): void
    {
        $this->users->deletePushSubscription($user, $endpoint);
    }
}
