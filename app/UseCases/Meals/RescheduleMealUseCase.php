<?php

declare(strict_types=1);

namespace App\UseCases\Meals;

use App\Data\PlannedMealData;
use App\Models\PlannedMeal;
use App\Repositories\MealPlanRepository;
use Carbon\CarbonImmutable;

/** Dropping a meal card onto another day in the planner. */
class RescheduleMealUseCase
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function execute(PlannedMeal $meal, CarbonImmutable $day): PlannedMealData
    {
        return PlannedMealData::fromModel($this->meals->reschedule($meal, $day));
    }
}
