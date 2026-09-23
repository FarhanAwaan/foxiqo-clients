#!/usr/bin/env bash
#
# Production deploy: pull latest code, install dependencies, migrate, and
# rebuild Laravel's caches (including the event cache — see the
# StartZoomProvisioning listener, which silently stopped firing once
# because a stale bootstrap/cache/events.php predated it).
#
# Run this ON the production server, from the project root:
#   ./deploy.sh

set -euo pipefail
cd "$(dirname "$0")"

# Guarantees the site comes back out of maintenance mode no matter what —
# if any step below fails, `set -e` aborts the script immediately, and
# without this trap the site would be stuck down until someone runs
# `artisan up` by hand. (The scheduler now also runs the queue worker
# evenInMaintenanceMode() as a second layer of defense, but this trap is
# the fix that stops the site itself from staying down.)
trap 'php artisan up || true' EXIT

echo "==> Entering maintenance mode"
php artisan down --retry=30 || true

echo "==> Pulling latest code"
git pull origin production

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

# Clear BEFORE migrating, not after: while the previous deploy's config cache is
# still in place, config() knows nothing a release added (e.g. the permission
# tables migration reads config('permission.*') and aborts without it).
echo "==> Clearing stale caches"
php artisan optimize:clear

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Rebuilding caches (config, routes, views, events)"
php artisan optimize
php artisan event:cache

echo "==> Restarting queue workers"
php artisan queue:restart

echo "==> Leaving maintenance mode"
php artisan up

echo "Deploy complete."
