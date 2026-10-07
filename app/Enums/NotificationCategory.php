<?php

declare(strict_types=1);

namespace App\Enums;

/** Groups household notifications so people can turn push off per area. */
enum NotificationCategory: string
{
    case Chores = 'chores';
    case Calendar = 'calendar';
    case Meals = 'meals';

    public function label(): string
    {
        return match ($this) {
            self::Chores => 'Chores',
            self::Calendar => 'Calendar',
            self::Meals => 'Meals',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Chores => 'Chores assigned to you, changes to your chores, and reminders',
            self::Calendar => 'Events you’re invited to, and changes to them',
            self::Meals => 'Meals you’re cooking, and changes to them',
        };
    }
}
