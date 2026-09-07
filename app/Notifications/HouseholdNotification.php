<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

abstract class HouseholdNotification extends Notification implements ShouldBroadcastNow
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
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

    abstract protected function householdId(): int;

    abstract protected function notificationType(): string;
}
