<?php

declare(strict_types=1);

namespace App\Enums;

/** The four household roles from the design, and what each is allowed to see/do. */
enum HouseholdRole: string
{
    case Owner = 'owner';
    case Adult = 'adult';
    case Teen = 'teen';
    case Child = 'child';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Adult => 'Adult',
            self::Teen => 'Teen',
            self::Child => 'Child',
        };
    }

    /**
     * Teen and Child are for under-18s, who may only join with a parent or
     * guardian's agreement (see the Terms of Service).
     */
    public function isForMinors(): bool
    {
        return $this === self::Teen || $this === self::Child;
    }

    public function colour(): Palette
    {
        return match ($this) {
            self::Owner => Palette::Coral,
            self::Adult => Palette::Mint,
            self::Teen => Palette::Sun,
            self::Child => Palette::Sky,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full access, and the only role that can delete the household.',
            self::Adult => 'Runs day-to-day life — meals, chores, calendar, budget and documents. No access to household settings or membership.',
            self::Teen => 'Meals, shopping and chores to view; can manage the calendar, and tick off their own chores and shopping items.',
            self::Child => 'A simplified view: the family calendar, and ticking off their own chores.',
        };
    }
}
