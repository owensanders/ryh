<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealDeletedNotification;

class DeletePlannedMealAction
{
    public function handle(PlannedMeal $meal, User $actor): void
    {
        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealDeletedNotification($meal, $actor));
        }

        $meal->delete();
    }
}
