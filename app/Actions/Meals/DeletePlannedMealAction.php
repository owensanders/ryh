<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\Household;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealDeletedNotification;

class DeletePlannedMealAction
{
    public function handle(Household $household, PlannedMeal $meal, User $actor): void
    {
        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealDeletedNotification($household, $meal, $actor));
        }

        $meal->delete();
    }
}
