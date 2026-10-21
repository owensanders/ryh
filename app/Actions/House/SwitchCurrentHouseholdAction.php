<?php

declare(strict_types=1);

namespace App\Actions\House;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SwitchCurrentHouseholdAction
{
    public function handle(User $user, int $householdId): void
    {
        // The request validates this too; a household they aren't in doesn't exist for them.
        if (! $user->belongsToHousehold($householdId)) {
            throw (new ModelNotFoundException())->setModel(Household::class, [$householdId]);
        }

        $user->rememberHousehold($householdId);
    }
}
