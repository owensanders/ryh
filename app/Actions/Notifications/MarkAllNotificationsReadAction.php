<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;
use App\Repositories\NotificationRepository;

class MarkAllNotificationsReadAction
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    public function handle(User $user): void
    {
        $this->notifications->markAllReadFor($user);
    }
}
