#!/bin/sh
set -e

echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/runtime 2>/dev/null || true
chown -R www-data:www-data /var/www/html/web/assets 2>/dev/null || true
chmod -R 755 /var/www/html/runtime 2>/dev/null || true
chmod -R 755 /var/www/html/web/assets 2>/dev/null || true

echo "Starting PHP-FPM..."
exec php-fpm