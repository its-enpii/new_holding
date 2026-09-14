#!/bin/sh
# Entrypoint for the Holding PHP-FPM container.
#
#   1. Ensure storage / bootstrap directories exist (idempotent).
#   2. Run composer install if vendor/ is missing (first boot).
#   3. Re-discover packages when composer.json is newer than the manifest.
#   4. Hand off to CMD (php-fpm, queue:work, artisan, ...).
#
# Keep this script FAST — it runs on every container start.

set -eu

mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing — running composer install"
    composer install --no-interaction --no-scripts --prefer-dist
    php artisan package:discover --ansi >/dev/null || true
fi

if [ composer.json -nt bootstrap/cache/packages.php ] 2>/dev/null; then
    echo "[entrypoint] composer.json newer than cache — re-discovering packages"
    php artisan package:discover --ansi >/dev/null
fi

# The bind-mounted host tree hides the image's Vite build. Materialise it once
# so a fresh clone serves assets without running `npm run build` first.
if [ ! -f public/build/manifest.json ] && [ -d /opt/holding/build ]; then
    echo "[entrypoint] public/build missing — copying image assets"
    rm -rf public/build
    cp -a /opt/holding/build public/build
fi

exec "$@"
