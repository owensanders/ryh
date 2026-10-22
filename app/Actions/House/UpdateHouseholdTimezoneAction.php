<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;

/** Where the household lives: its days, and so its calendar and meal planner, turn over at that midnight. */
class UpdateHouseholdTimezoneAction
{
    public function handle(Household $household, string $timezone): Household
    {
        return tap($household)->update(['timezone' => $timezone])->refresh();
    }
}
