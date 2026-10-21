<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Household;
use App\Models\User;

/**
 * For /app and links from before households were in the URL: back to the
 * household they were last in, the only one they have, or the picker when
 * there's more than one and none to go back to.
 */
class ResolveLandingAction
{
    /**
     * @param  string  $path  The screen they were after within a household, e.g. "budget".
     *
     * @return string Where to send them.
     */
    public function handle(User $user, string $path = 'dashboard', ?string $query = null): string
    {
        $householdIds = $user->householdIds();

        if ($householdIds->isEmpty()) {
            return route('household.setup', absolute: false);
        }

        $householdId = match (true) {
            $user->last_household_id !== null && $householdIds->contains($user->last_household_id) => $user->last_household_id,
            $householdIds->count() === 1 => $householdIds->first(),
            default => null,
        };

        $slug = $householdId !== null ? Household::find($householdId)?->slug : null;

        if ($slug === null) {
            return route('household.select', absolute: false);
        }

        return "/h/{$slug}/".ltrim($path, '/').($query !== null ? "?{$query}" : '');
    }
}
