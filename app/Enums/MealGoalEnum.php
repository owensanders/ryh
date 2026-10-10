<?php

declare(strict_types=1);

namespace App\Enums;

enum MealGoalEnum: string
{
    case EatHealthier = 'Eat healthier';
    case SpendLess = 'Spend less';
    case EatMoreVeg = 'Eat more veg';
    case QuickWeeknights = 'Quick weeknights';
    case BatchCook = 'Batch cook';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $goal) => $goal->value, self::cases());
    }
}
