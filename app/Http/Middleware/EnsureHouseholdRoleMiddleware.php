<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHouseholdRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->currentRole();

        abort_if($role === null || ! in_array($role->value, $roles, true), 403);

        return $next($request);
    }
}
