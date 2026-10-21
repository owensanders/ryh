<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Models\Chore;
use App\Models\User;

class ChoreAssignedNotification extends HouseholdNotification
{
    public function __construct(
        private readonly Chore $chore,
        private readonly User $actor,
    ) {}

    /**
     * @param  User  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->notificationType(),
            'chore_id' => $this->chore->id,
            'user_id' => $notifiable->id,
            'body' => "{$this->actor->name} assigned you \"{$this->chore->name}\"",
            'url' => $this->householdUrl('chores.index'),
        ];
    }

    public function category(): NotificationCategoryEnum
    {
        return NotificationCategoryEnum::Chores;
    }

    protected function householdId(): int
    {
        return $this->chore->household_id;
    }

    protected function notificationType(): string
    {
        return 'chore_assigned';
    }
}
