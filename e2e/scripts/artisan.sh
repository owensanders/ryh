#!/usr/bin/env sh
# Runs artisan against the e2e environment (.env.e2e): inside the Sail app
# container locally, straight on the runner in CI.
set -e

if [ -n "$CI" ]; then
    APP_ENV=e2e exec php artisan "$@"
fi

exec docker exec -i -e APP_ENV=e2e ryh-app php artisan "$@"
