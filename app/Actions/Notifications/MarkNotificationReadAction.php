<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;

/** Opening a notification reads it. The bell goes to its page itself, so a failure here can't stop it. */
class MarkNotificationReadAction
{
    public function handle(User $user, string $id): void
    {
        // Only their own: anyone else's is a 404.
        $user->notifications()->findOrFail($id)->markAsRead();
    }
}
