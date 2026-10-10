<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;

/**
 * The live half of a household notification: one person's bell, pushed
 * through the queue. It carries the finished payload rather than the
 * notification, so a job for a since-deleted chore or meal has nothing to
 * reload — and a slow or unreachable Reverb only delays the bell, never fails
 * the request that triggered it.
 */
class UserNotificationBroadcast implements ShouldBroadcast, ShouldDispatchAfterCommit, ShouldRescue
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public readonly int $userId,
        public readonly array $payload,
    ) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->userId}")];
    }

    /** Echo's notification listener (`useEchoNotification`) listens for exactly this name. */
    public function broadcastAs(): string
    {
        return BroadcastNotificationCreated::class;
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
