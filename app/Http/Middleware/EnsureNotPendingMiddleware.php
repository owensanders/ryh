<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Traits\ResolvesUserTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotPendingMiddleware
{
    use ResolvesUserTrait;

    /**
     * Someone who has just asked to join a household waits on the "request
     * sent" screen until they're let in: they can't set up or join another
     * one in the meantime.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->user($request)->lastMembership()?->pending === true) {
            return redirect()->route('household.done');
        }

        return $next($request);
    }
}
