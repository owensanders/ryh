#!/usr/bin/env sh
# Opens a temporary public HTTPS address for the local app, so it can be
# installed and sent push notifications on a real phone. Uses a Cloudflare
# quick tunnel (no account needed); the address changes every time.
#
#   ./scripts/tunnel.sh        start it and print the address
#   ./scripts/tunnel.sh stop   close it
#
# The phone loads the production build, so run `sail npm run build` with the
# Vite dev server stopped first. Live updates (Reverb) don't go through the
# tunnel; everything else does. See "Testing on a phone" in the README.
set -e

if [ "$1" = "stop" ]; then
    docker stop home-hub-tunnel >/dev/null && echo "Tunnel closed."
    exit 0
fi

if ! grep -q '^TRUSTED_PROXIES=' .env 2>/dev/null; then
    echo "Add TRUSTED_PROXIES=* to .env first, so links through the tunnel use https." >&2
    exit 1
fi

if [ -f public/hot ]; then
    echo "The Vite dev server is running; the phone can't reach it. Stop it and run \`sail npm run build\` first." >&2
    exit 1
fi

./vendor/bin/sail up -d tunnel >/dev/null 2>&1

printf 'Waiting for the tunnel'
for _ in $(seq 1 30); do
    url=$(docker logs home-hub-tunnel 2>&1 | grep -o 'https://[a-z0-9-]*\.trycloudflare\.com' | tail -1)
    if [ -n "$url" ]; then
        printf '\n\nOpen this on your phone:\n\n    %s\n\n' "$url"
        exit 0
    fi
    printf '.'
    sleep 1
done

echo "\nNo address yet — check \`docker logs home-hub-tunnel\`." >&2
exit 1
