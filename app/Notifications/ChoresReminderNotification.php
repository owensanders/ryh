<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Household;
use App\Models\User;

class ChoresReminderNotification extends HouseholdNotification
{
    public function __construct(
        private readonly Household $household,
        private readonly int $outstandingCount,
    ) {}

    /**
     * @param  User  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $chore = $this->outstandingCount === 1 ? 'chore' : 'chores';

        return [
            'type' => $this->notificationType(),
            'user_id' => $notifiable->id,
            'body' => "You have {$this->outstandingCount} outstanding {$chore}",
            'url' => route('chores.index'),
        ];
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Chores;
    }

    protected function householdId(): int
    {
        return $this->household->id;
    }

    protected function notificationType(): string
    {
        return 'chores_reminder';
    }
}
