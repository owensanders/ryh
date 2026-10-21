<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventAssignedNotification extends HouseholdNotification
{
    public function __construct(
        private readonly CalendarEvent $event,
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
            'event_id' => $this->event->id,
            'user_id' => $notifiable->id,
            'body' => "{$this->actor->name} added you to \"{$this->event->title}\"",
            'url' => $this->householdUrl('calendar.index'),
        ];
    }

    public function category(): NotificationCategoryEnum
    {
        return NotificationCategoryEnum::Calendar;
    }

    protected function householdId(): int
    {
        return $this->event->household_id;
    }

    protected function notificationType(): string
    {
        return 'calendar_event_assigned';
    }
}
