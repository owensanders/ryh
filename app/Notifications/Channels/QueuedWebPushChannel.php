<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\User;
use App\Notifications\HouseholdNotification;
use App\Notifications\PushNotification;

/**
 * Household notifications are sent synchronously so the bell updates at once;
 * this channel hands the push part to the queue, so a slow push service never
 * holds up the request that triggered it.
 */
class QueuedWebPushChannel
{
    public function send(User $notifiable, HouseholdNotification $notification): void
    {
        $notifiable->notify(new PushNotification($notification->toPushMessage($notifiable)));
    }
}
