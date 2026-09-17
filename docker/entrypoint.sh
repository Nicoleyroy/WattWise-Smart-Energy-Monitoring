#!/bin/sh
set -eu

port="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

php artisan storage:link --quiet || true
php artisan config:cache
php artisan view:cache

exec apache2-foreground
