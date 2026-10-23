<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headers that tell the browser to keep RYH's pages to themselves: no other
 * site may frame them (clickjacking), files are taken as the type they're
 * sent as, other sites learn only where a link came from, and the browser
 * features RYH doesn't use stay off. Over HTTPS, the browser is also told to
 * use HTTPS from then on.
 *
 * There's no full Content-Security-Policy: the page shell's inline scripts
 * (Ziggy's route list, Vite in development) would need nonces first.
 */
class SecurityHeadersMiddleware
{
    private const int HSTS_SECONDS = 31_536_000;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age='.self::HSTS_SECONDS.'; includeSubDomains');
        }

        return $response;
    }
}
