<?php

declare(strict_types=1);

namespace App\Enums;

enum MealSlotEnum: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
