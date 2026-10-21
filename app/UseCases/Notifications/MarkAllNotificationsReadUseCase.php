<?php

declare(strict_types=1);

namespace App\UseCases\Notifications;

use App\Models\User;
use App\Repositories\NotificationRepository;

class MarkAllNotificationsReadUseCase
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    public function execute(User $user): void
    {
        $this->notifications->markAllReadFor($user);
    }
}
