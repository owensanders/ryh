<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Household;
use App\Repositories\HouseholdRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\UserRepository;
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

    public function __construct(
        private readonly HouseholdRepository $households,
        private readonly MembershipRepository $memberships,
        private readonly UserRepository $users,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->user($request);
        $slug = $request->route('household');
        $household = is_string($slug) ? $this->households->findBySlug($slug) : null;

        // Someone else's household doesn't exist, as far as they can tell.
        $membership = $household !== null ? $this->memberships->find($user, $household) : null;

        if ($household === null || $membership === null) {
            throw (new ModelNotFoundException())->setModel(Household::class, is_string($slug) ? [$slug] : []);
        }

        // Joined by code but not approved yet: the owner has to let them in first.
        if ($membership->pending) {
            return redirect()->route('household.done');
        }

        CurrentHouseholdHelper::set($request, $household, $membership);

        // route('chores.index') here fills in this household; and controllers
        // don't receive it as an argument, or it would take the place of their
        // own optional parameters (ShoppingListController::index's $slug).
        URL::defaults(['household' => $household->slug]);
        $request->route()?->forgetParameter('household');

        if ($user->last_household_id !== $household->id) {
            $this->users->setLastHousehold($user, $household->id);
        }

        return $next($request);
    }
}
