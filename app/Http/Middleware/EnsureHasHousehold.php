<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Traits\ResolvesUserTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasHousehold
{
    use ResolvesUserTrait;

    /**
     * A verified user with no household yet has nowhere else useful to go —
     * send them to finish the setup wizard instead of 403ing on every page.
     * A user in more than one household picks which one before anything else
     * loads. A user who joined by code but isn't approved yet doesn't get in
     * either; the owner has to action their pending membership first.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->user($request);
        $householdIds = $user->households()->pluck('households.id');

        if ($householdIds->isEmpty()) {
            return redirect()->route('household.setup');
        }

        if ($householdIds->count() === 1) {
            if ($user->current_household_id !== $householdIds->first()) {
                $user->forceFill(['current_household_id' => $householdIds->first()])->save();
            }
        } elseif ($user->current_household_id === null || ! $householdIds->contains($user->current_household_id)) {
            return redirect()->route('household.select');
        }

        if ($user->currentMembership()?->pending === true) {
            return redirect()->route('household.done');
        }

        return $next($request);
    }
}
