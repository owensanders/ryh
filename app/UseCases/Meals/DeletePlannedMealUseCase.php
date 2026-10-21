<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealDeletedNotification;
use App\Repositories\MealPlanRepository;

class DeletePlannedMealUseCase
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function execute(PlannedMeal $meal, User $actor): void
    {
        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealDeletedNotification($meal, $actor));
        }

        $this->meals->delete($meal);
    }
}
