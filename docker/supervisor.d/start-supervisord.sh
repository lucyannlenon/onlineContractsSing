#!/bin/bash
set -euo pipefail

# Garante que o consumidor do scheduler (www-data) consiga escrever em /app/var
mkdir -p /app/var/cache /app/var/log
chown -R www-data:www-data /app/var || true

echo "Starting supervisord (php-fpm + scheduler)..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
