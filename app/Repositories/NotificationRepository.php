<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationRepository
{
    /** @return Collection<int, DatabaseNotification> Newest first. */
    public function latestFor(User $user, int $limit): Collection
    {
        return $user->notifications()->latest()->limit($limit)->get();
    }

    /** One of their own notifications — null for anyone else's. */
    public function findFor(User $user, string $id): ?DatabaseNotification
    {
        return $user->notifications()->find($id);
    }

    public function markRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllReadFor(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function deleteAllFor(User $user): void
    {
        $user->notifications()->delete();
    }
}
