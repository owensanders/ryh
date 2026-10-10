<?php

declare(strict_types=1);

namespace App\UseCases\Notifications;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Models\User;

class MarkAllNotificationsReadUseCase
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function execute(User $user): void
    {
        $this->notifications->markAllReadFor($user);
    }
}
