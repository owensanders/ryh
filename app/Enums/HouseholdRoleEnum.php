<?php

declare(strict_types=1);

namespace App\Enums;

/** The four household roles from the design, and what each is allowed to see/do. */
enum HouseholdRoleEnum: string
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

    /**
     * Owner and Adult: everyone who creates, edits and deletes household data.
     *
     * @return list<self>
     */
    public static function managers(): array
    {
        return [self::Owner, self::Adult];
    }

    /**
     * Managers plus Teen: the calendar and ticking off shopping are theirs to run too.
     *
     * @return list<self>
     */
    public static function managersAndTeens(): array
    {
        return [self::Owner, self::Adult, self::Teen];
    }

    public function colour(): PaletteEnum
    {
        return match ($this) {
            self::Owner => PaletteEnum::Coral,
            self::Adult => PaletteEnum::Mint,
            self::Teen => PaletteEnum::Sun,
            self::Child => PaletteEnum::Sky,
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
