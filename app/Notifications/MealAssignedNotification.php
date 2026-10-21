<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Models\PlannedMeal;
use App\Models\User;

class MealAssignedNotification extends HouseholdNotification
{
    public function __construct(
        private readonly PlannedMeal $meal,
        private readonly User $actor,
    ) {}

    /**
     * @param  User  $notifiable
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->notificationType(),
            'meal_id' => $this->meal->id,
            'user_id' => $notifiable->id,
            'body' => "{$this->actor->name} assigned you to cook \"{$this->meal->recipe->name}\"",
            'url' => $this->householdUrl('meals.index'),
        ];
    }

    public function category(): NotificationCategoryEnum
    {
        return NotificationCategoryEnum::Meals;
    }

    protected function householdId(): int
    {
        return $this->meal->household_id;
    }

    protected function notificationType(): string
    {
        return 'meal_assigned';
    }
}
