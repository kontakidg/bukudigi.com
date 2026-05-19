#!/usr/bin/env bash
# bukudigi.com — deploy script
# Usage:
#   bash deploy.sh              standard: clear cache + migrate
#   bash deploy.sh --composer   + composer install
#   bash deploy.sh --build      + npm install + vite build
#   bash deploy.sh --full       all of the above

set -e

DO_COMPOSER=0
DO_BUILD=0
for arg in "$@"; do
    case "$arg" in
        --composer) DO_COMPOSER=1 ;;
        --build)    DO_BUILD=1 ;;
        --full)     DO_COMPOSER=1; DO_BUILD=1 ;;
    esac
done

cd "$(dirname "$0")"

echo "📦 bukudigi.com deploy — $(date '+%Y-%m-%d %H:%M:%S')"

# Enter maintenance mode for the duration of deploy
php artisan down --render="errors::503" --secret="deploy-secret-$(date +%s)" || true

trap 'php artisan up || true' EXIT

if [[ $DO_COMPOSER -eq 1 ]]; then
    echo "→ composer install"
    composer install --no-dev --optimize-autoloader --no-interaction
fi

if [[ $DO_BUILD -eq 1 ]]; then
    echo "→ npm install + build"
    if [[ ! -d node_modules ]]; then
        npm install
    fi
    npm run build
fi

echo "→ clear caches"
php artisan optimize:clear

echo "→ migrate"
php artisan migrate --force

echo "→ cache config + routes + views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "→ storage:link (idempotent)"
php artisan storage:link 2>/dev/null || true

echo "→ restart queue worker (Supervisor)"
if command -v supervisorctl >/dev/null 2>&1; then
    sudo supervisorctl restart bukudigi-queue:* 2>/dev/null || \
        echo "  (Supervisor not configured yet — skip)"
fi

echo "✓ deploy done"
