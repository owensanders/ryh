<?php

declare(strict_types=1);

namespace App\UseCases\Notifications;

use App\Models\User;
use App\Repositories\NotificationRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

/** Opening a notification is reading it. */
class OpenNotificationUseCase
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    /** @return string|null Where it points, if anywhere. */
    public function execute(User $user, string $id): ?string
    {
        $notification = $this->notifications->findFor($user, $id);

        if ($notification === null) {
            throw (new ModelNotFoundException())->setModel(DatabaseNotification::class, [$id]);
        }

        $this->notifications->markRead($notification);

        $url = $notification->data['url'] ?? null;

        return is_string($url) ? $url : null;
    }
}
