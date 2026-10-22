<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Takes {household} out of the route's parameters once `->can()` has passed
 * it to the policy, so controllers don't receive it as an argument: it would
 * take the place of their own optional parameters (ShoppingListController::index's
 * $slug). Ordered after Authorize in bootstrap/app.php; controllers read the
 * household with ResolvesHouseholdTrait.
 */
class ForgetHouseholdParameterMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->route()?->forgetParameter('household');

        return $next($request);
    }
}
