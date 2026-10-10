<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Events\UserNotificationBroadcastEvent;
use App\Models\User;
use App\Notifications\HouseholdNotification;

/**
 * Hands the live bell update to the queue, on the recipient's own private
 * channel, so only they receive it and a Reverb outage can't fail the request.
 */
class QueuedBroadcastChannel
{
    public function send(User $notifiable, HouseholdNotification $notification): void
    {
        event(new UserNotificationBroadcastEvent($notifiable->id, [
            ...$notification->toArray($notifiable),
            'id' => $notification->id,
        ]));
    }
}
