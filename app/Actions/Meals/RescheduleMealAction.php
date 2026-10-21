<?php

declare(strict_types=1);

namespace App\Actions\Meals;

use App\Models\PlannedMeal;
use Carbon\CarbonImmutable;

class RescheduleMealAction
{
    public function handle(PlannedMeal $meal, CarbonImmutable $day): PlannedMeal
    {
        $meal->update(['planned_on' => $day->toDateString()]);

        return $meal->refresh();
    }
}
