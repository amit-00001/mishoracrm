#!/bin/sh
# Container entrypoint (Voroa / Render): prepare storage, migrate, then php-fpm + nginx.
# Everything goes to stdout/stderr so it shows up in the platform's runtime logs.

echo "[boot] $(date -u +%T) starting"

P=$(printf '%s' "${PORT:-3000}" | tr -cd '0-9')
sed -i "s/__*PORT__*/${P}/g" /etc/nginx/http.d/default.conf
echo "[boot] nginx will listen on port ${P}"

cd /var/www/html || exit 1

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache

# Same as `php artisan storage:link`, without booting Laravel again (slow on a 0.1 vCPU instance).
ln -sfn /var/www/html/storage/app/public public/storage

# Belt and braces for the "stat() ... Permission denied" 404s: let any user walk into the app root
# and read the (public, small) web root, whatever modes the build context arrived with.
chmod a+x /var/www/html
chmod -R a+rX public

echo "[boot] $(grep -m1 '^user ' /etc/nginx/nginx.conf)"
ls -ld /var/www /var/www/html /var/www/html/public /var/www/html/public/index.php | sed 's/^/[boot] /'

php artisan migrate --force || echo 'migrate failed'
echo "[boot] $(date -u +%T) migrate step done"

php artisan db:seed --class=PortalTestCustomerSeeder --force || echo 'test customer seed failed'
echo "[boot] $(date -u +%T) seed step done"

# artisan runs as root; hand whatever it created back to php-fpm's user.
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

php-fpm -D
echo "[boot] $(date -u +%T) php-fpm started, starting nginx"
netstat -ltn | sed 's/^/[boot] /'

exec nginx -g 'daemon off;'
