#!/usr/bin/env bash
# Release routine (cPanel/SSH). Usage: ./deploy.sh
set -euo pipefail
php artisan down || true
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan portfolios:import
php artisan portfolios:validate || true
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
curl -fsS "${APP_URL:-http://localhost}/up" >/dev/null && echo "Smoke OK" || echo "Smoke FAILED"
