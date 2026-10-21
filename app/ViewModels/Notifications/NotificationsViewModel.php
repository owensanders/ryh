<?php

declare(strict_types=1);

namespace App\ViewModels\Notifications;

use App\Data\NotificationData;
use App\Models\User;

class NotificationsViewModel
{
    /** The bell shows this many, newest first. */
    private const int LIMIT = 20;

    /** @return array<int, NotificationData> */
    public function toData(User $user): array
    {
        return $user->notifications()->latest()->limit(self::LIMIT)->get()
            ->map(NotificationData::fromModel(...))
            ->values()
            ->all();
    }
}
