<?php

declare(strict_types=1);

namespace App\Enums;

enum KitchenEquipment: string
{
    case Oven = 'Oven';
    case Hob = 'Hob';
    case Microwave = 'Microwave';
    case AirFryer = 'Air fryer';
    case SlowCooker = 'Slow cooker';
    case PressureCooker = 'Pressure cooker';
    case Grill = 'Grill';
    case Bbq = 'BBQ';
    case RiceCooker = 'Rice cooker';
    case FoodProcessor = 'Food processor';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $equipment) => $equipment->value, self::cases());
    }
}
