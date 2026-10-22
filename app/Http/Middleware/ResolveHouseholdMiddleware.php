<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Household;
use App\Support\CurrentHouseholdHelper;
use App\Traits\ResolvesUserTrait;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every household screen lives under /h/{household}, so the household a
 * request acts on is the one in its URL. Two tabs on two households each act
 * on their own; nothing is read from the user row.
 */
class ResolveHouseholdMiddleware
{
    use ResolvesUserTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->user($request);
        $household = $request->route('household');

        // Bound from the slug in AppServiceProvider. Someone else's household
        // doesn't exist, as far as they can tell.
        // Read fresh (a test's next request reuses the same user), and kept for
        // this request's policy checks.
        $user->forgetMemberships();
        $membership = $household instanceof Household ? $user->membershipIn($household->id) : null;

        if (! $household instanceof Household || $membership === null) {
            throw (new ModelNotFoundException())->setModel(Household::class);
        }

        // Joined by code but not approved yet: the owner has to let them in first.
        if ($membership->pending) {
            return redirect()->route('household.done');
        }

        CurrentHouseholdHelper::set($request, $household);

        // route('chores.index') here fills in this household. The route
        // parameter itself stays until the policy check has used it
        // (ForgetHouseholdParameterMiddleware).
        URL::defaults(['household' => $household->slug]);

        if ($user->last_household_id !== $household->id) {
            $user->rememberHousehold($household->id);
        }

        return $next($request);
    }
}
