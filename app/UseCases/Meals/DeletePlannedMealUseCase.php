<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Contracts\Repositories\MealPlanRepositoryInterface;
use App\Models\PlannedMeal;
use App\Models\User;
use App\Notifications\MealDeletedNotification;

class DeletePlannedMealUseCase
{
    public function __construct(private readonly MealPlanRepositoryInterface $meals) {}

    public function execute(PlannedMeal $meal, User $actor): void
    {
        if ($meal->cook_id !== null && $meal->cook_id !== $actor->id) {
            $meal->cook?->notify(new MealDeletedNotification($meal, $actor));
        }

        $this->meals->delete($meal);
    }
}
