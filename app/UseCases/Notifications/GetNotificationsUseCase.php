<?php

declare(strict_types=1);

namespace App\UseCases\Notifications;

use App\Data\NotificationData;
use App\Models\User;
use App\Repositories\NotificationRepository;

class GetNotificationsUseCase
{
    /** The bell shows this many, newest first. */
    private const int LIMIT = 20;

    public function __construct(private readonly NotificationRepository $notifications) {}

    /** @return array<int, NotificationData> */
    public function execute(User $user): array
    {
        return $this->notifications->latestFor($user, self::LIMIT)
            ->map(NotificationData::fromModel(...))
            ->values()
            ->all();
    }
}
