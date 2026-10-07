<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Household;
use App\Models\User;
use App\Notifications\Channels\QueuedWebPushChannel;
use App\Notifications\Messages\PushMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

abstract class HouseholdNotification extends Notification implements ShouldBroadcastNow
{
    /**
     * The bell (database + broadcast) always; a push too when the person has
     * turned it on for a device and hasn't opted out of this category.
     *
     * @param  User  $notifiable
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable->wantsPushFor($this->category()) && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = QueuedWebPushChannel::class;
        }

        return $channels;
    }

    /** Titled with the household's name, so someone in two households knows which one it's about. */
    public function toPushMessage(User $notifiable): PushMessage
    {
        $data = $this->toArray($notifiable);

        return new PushMessage(
            title: Household::find($this->householdId())->name ?? config('app.name'),
            body: $data['body'],
            url: $data['url'],
            tag: "{$this->notificationType()}-{$notifiable->id}",
        );
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("household.{$this->householdId()}")];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Without this, BroadcastNotificationCreated overwrites the 'type' key from
     * toArray() with the notification's FQCN, so the live-pushed payload's type
     * silently disagrees with the type persisted to the database.
     */
    public function broadcastType(): string
    {
        return $this->notificationType();
    }

    /** @return array<string, mixed> */
    abstract public function toArray(object $notifiable): array;

    abstract public function category(): NotificationCategory;

    abstract protected function householdId(): int;

    abstract protected function notificationType(): string;
}
