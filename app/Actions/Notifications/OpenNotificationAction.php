<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;

/** Opening a notification is reading it. */
class OpenNotificationAction
{
    /** @return string|null Where it points, if anywhere. */
    public function handle(User $user, string $id): ?string
    {
        // Only their own: anyone else's is a 404.
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return is_string($url) ? $url : null;
    }
}
