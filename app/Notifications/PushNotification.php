<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Messages\PushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Delivers a push to every device the person has turned notifications on for. */
class PushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PushMessage $message)
    {
        // A push about a chore saved inside a transaction must not go out if it rolls back.
        $this->afterCommit();
    }

    /** @return list<class-string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage())
            ->title($this->message->title)
            ->body($this->message->body)
            ->icon('/icons/icon-192.png')
            ->tag($this->message->tag)
            ->data(['url' => $this->message->url]);
    }
}
