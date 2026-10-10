<?php

declare(strict_types=1);

namespace App\UseCases\Notifications;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Models\User;

/** Opening a notification is reading it. */
class OpenNotificationUseCase
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    /** @return string|null Where it points, if anywhere. */
    public function execute(User $user, string $id): ?string
    {
        $notification = $this->notifications->findFor($user, $id);

        abort_if($notification === null, 404);

        $this->notifications->markRead($notification);

        $url = $notification->data['url'] ?? null;

        return is_string($url) ? $url : null;
    }
}
