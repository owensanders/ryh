<?php

declare(strict_types=1);

namespace App\UseCases\Notifications;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Data\NotificationData;
use App\Models\User;

class GetNotificationsUseCase
{
    /** The bell shows this many, newest first. */
    private const int LIMIT = 20;

    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    /** @return array<int, NotificationData> */
    public function execute(User $user): array
    {
        return $this->notifications->latestFor($user, self::LIMIT)
            ->map(NotificationData::fromModel(...))
            ->values()
            ->all();
    }
}
