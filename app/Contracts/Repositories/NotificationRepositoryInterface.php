<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/** The bell: each person's own database notifications. */
interface NotificationRepositoryInterface
{
    /** @return Collection<int, DatabaseNotification> Newest first. */
    public function latestFor(User $user, int $limit): Collection;

    /** One of their own notifications — null for anyone else's. */
    public function findFor(User $user, string $id): ?DatabaseNotification;

    public function markRead(DatabaseNotification $notification): void;

    public function markAllReadFor(User $user): void;

    public function deleteAllFor(User $user): void;
}
