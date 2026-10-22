<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Models\Chore;
use App\Models\Household;
use App\Models\User;

class ChoreDeletedNotification extends HouseholdNotification
{
    public function __construct(
        Household $household,
        private readonly Chore $chore,
        private readonly User $actor,
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
        return [
            'type' => $this->notificationType(),
            'user_id' => $notifiable->id,
            'body' => "{$this->actor->name} deleted \"{$this->chore->name}\"",
            'url' => $this->householdUrl('chores.index'),
        ];
    }

    public function category(): NotificationCategoryEnum
    {
        return NotificationCategoryEnum::Chores;
    }

    protected function notificationType(): string
    {
        return 'chore_deleted';
    }
}
