<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PlannedMeal;
use App\Models\User;

class MealDeletedNotification extends HouseholdNotification
{
    public function __construct(
        private readonly PlannedMeal $meal,
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
            'user_id' => $notifiable->id,
            'body' => "{$this->actor->name} removed \"{$this->meal->recipe->name}\" from the meal plan",
            'url' => route('meals.index'),
        ];
    }

    protected function householdId(): int
    {
        return $this->meal->household_id;
    }

    protected function notificationType(): string
    {
        return 'meal_deleted';
    }
}
