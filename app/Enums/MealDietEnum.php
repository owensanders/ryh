<?php

declare(strict_types=1);

namespace App\Enums;

enum MealDietEnum: string
{
    case Vegetarian = 'Vegetarian';
    case Vegan = 'Vegan';
    case Pescatarian = 'Pescatarian';
    case GlutenFree = 'Gluten free';
    case DairyFree = 'Dairy free';
    case NutFree = 'Nut free';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $diet) => $diet->value, self::cases());
    }
}
