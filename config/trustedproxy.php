<?php

declare(strict_types=1);

return [

    /*
     * Proxies whose X-Forwarded-* headers Laravel should believe, as "*" or a
     * comma-separated list of IPs/CIDRs (read by the TrustProxies middleware).
     * Unset, none are trusted. Set it when the app sits behind a proxy that
     * terminates HTTPS — production's load balancer, or the local phone-testing
     * tunnel (scripts/tunnel.sh) — so URLs come out as https:// on its domain.
     */
    'proxies' => env('TRUSTED_PROXIES'),

];
