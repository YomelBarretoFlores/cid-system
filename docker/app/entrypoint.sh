#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

php docker/app/database.php wait

php artisan migrate --force

# Demo data only on an empty database, so a redeploy never seeds twice; the seeder itself refuses APP_ENV=production.
if [[ "${DEMO_SEED:-false}" == "true" ]]; then
    vacia=0
    php docker/app/database.php empty || vacia=$?
    if (( vacia > 1 )); then
        echo "Could not check whether the database is empty" >&2
        exit 1
    fi
    if (( vacia == 0 )); then
        php artisan db:seed --force
    else
        # An existing demo is completed without deleting anything; every piece of the complement checks first.
        # It is one transaction, so a failure leaves the demo as it was and must not keep it from starting.
        if ! php artisan db:seed --class='Database\Seeders\ComplementoDemoSeeder' --force; then
            echo "Warning: the demo complement failed; the demo starts without it" >&2
        fi
    fi
fi

php artisan storage:link --force
php artisan optimize
php artisan filament:optimize
chown -R www-data:www-data storage bootstrap/cache

exec supervisord -c /etc/supervisor/conf.d/ciln.conf
