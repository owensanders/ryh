<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealDeletedNotification;
use App\Repositories\MealPlanRepository;

class DeletePlannedMealAction
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function handle(PlannedMeal $meal, User $actor): void
    {
        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealDeletedNotification($meal, $actor));
        }

        $this->meals->delete($meal);
    }
}
