<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Models\Household;
use App\Models\User;

class ChoresReminderNotification extends HouseholdNotification
{
    public function __construct(
        Household $household,
        private readonly int $outstandingCount,
    ) {
        parent::__construct($household);
    }

    /**
     * @param  User  $notifiable
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $chore = $this->outstandingCount === 1 ? 'chore' : 'chores';

        return [
            'type' => $this->notificationType(),
            'user_id' => $notifiable->id,
            'body' => "You have {$this->outstandingCount} outstanding {$chore}",
            'url' => $this->householdUrl('chores.index'),
        ];
    }

    public function category(): NotificationCategoryEnum
    {
        return NotificationCategoryEnum::Chores;
    }

    protected function notificationType(): string
    {
        return 'chores_reminder';
    }
}
