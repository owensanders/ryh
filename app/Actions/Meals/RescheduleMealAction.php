<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Data\PlannedMealData;
use App\Models\PlannedMeal;
use App\Repositories\MealPlanRepository;
use Carbon\CarbonImmutable;

/** Dropping a meal card onto another day in the planner. */
class RescheduleMealAction
{
    public function __construct(private readonly MealPlanRepository $meals) {}

    public function handle(PlannedMeal $meal, CarbonImmutable $day): PlannedMealData
    {
        return PlannedMealData::fromModel($this->meals->reschedule($meal, $day));
    }
}
